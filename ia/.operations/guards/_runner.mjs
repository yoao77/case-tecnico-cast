#!/usr/bin/env node
/**
 * Runner dos guards. Executado pelo hook .git/hooks/pre-commit.
 *
 * Descobre todo *.mjs deste diretório (menos os que começam com "_"),
 * importa cada um e executa o export default passando o contexto do commit.
 * Se qualquer guard retornar problemas, o commit é abortado.
 *
 * Contrato de um guard — veja README.md deste diretório.
 */

import { execFileSync } from 'node:child_process';
import { readdirSync } from 'node:fs';
import { dirname, extname, join } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

if (process.env.SKIP_GUARDS) process.exit(0);

const COR = process.stdout.isTTY && !process.env.NO_COLOR;
const c = (codigo, texto) => (COR ? `\x1b[${codigo}m${texto}\x1b[0m` : texto);
const vermelho = (t) => c('31', t);
const amarelo = (t) => c('33', t);
const verde = (t) => c('32', t);
const cinza = (t) => c('90', t);
const negrito = (t) => c('1', t);

function git(args, opcoes = {}) {
  return execFileSync('git', args, { maxBuffer: 64 * 1024 * 1024, ...opcoes });
}

const RAIZ = git(['rev-parse', '--show-toplevel'], { encoding: 'utf8' }).trim();
const DIR_GUARDS = dirname(fileURLToPath(import.meta.url));

/** Arquivos no index (staged), já sem os deletados. */
function caminhosStaged() {
  const saida = git(
    ['diff', '--cached', '--name-only', '--diff-filter=ACMR', '-z'],
    { encoding: 'utf8' },
  );
  return saida.split('\0').filter(Boolean);
}

/**
 * Um arquivo do commit. Tudo é lido do INDEX (o que vai ser commitado),
 * nunca do working tree — o que está no disco pode não ser o que está staged.
 */
function criarArquivo(caminho) {
  let bufferCache;
  let conteudoCache;
  let adicionadasCache;

  const buffer = () => (bufferCache ??= git(['show', `:${caminho}`]));

  const arquivo = {
    caminho,
    ext: extname(caminho).toLowerCase(),

    /** true para binários (imagem, pdf...) — guards de texto devem pular. */
    ehBinario() {
      return buffer().includes(0);
    },

    /** Conteúdo completo do arquivo como está no index. */
    conteudo() {
      if (conteudoCache === undefined) {
        conteudoCache = arquivo.ehBinario() ? '' : buffer().toString('utf8');
      }
      return conteudoCache;
    },

    /** Todas as linhas: [{ numero, texto }] — numero começa em 1. */
    linhas() {
      return arquivo
        .conteudo()
        .split('\n')
        .map((texto, i) => ({ numero: i + 1, texto }));
    },

    /**
     * Só as linhas ADICIONADAS neste commit: [{ numero, texto }].
     * É o que a maioria dos guards deve usar — assim código legado que
     * você não encostou não derruba o commit.
     */
    linhasAdicionadas() {
      if (adicionadasCache) return adicionadasCache;
      adicionadasCache = [];
      if (arquivo.ehBinario()) return adicionadasCache;

      const diff = git(
        ['diff', '--cached', '-U0', '--no-color', '--', caminho],
        { encoding: 'utf8' },
      );
      let numero = 0;
      for (const linha of diff.split('\n')) {
        const cabecalho = linha.match(/^@@ -\d+(?:,\d+)? \+(\d+)(?:,\d+)? @@/);
        if (cabecalho) {
          numero = Number(cabecalho[1]);
          continue;
        }
        if (linha.startsWith('+++')) continue;
        if (linha.startsWith('+')) {
          adicionadasCache.push({ numero, texto: linha.slice(1) });
          numero++;
        }
      }
      return adicionadasCache;
    },
  };

  return arquivo;
}

/** Aceita undefined | string | objeto | array e devolve sempre um array. */
function normalizarProblemas(retorno) {
  if (!retorno) return [];
  const lista = Array.isArray(retorno) ? retorno : [retorno];
  return lista
    .filter(Boolean)
    .map((p) => (typeof p === 'string' ? { mensagem: p } : p));
}

function formatarLocal(problema) {
  if (!problema.arquivo) return '';
  return problema.linha
    ? `${problema.arquivo}:${problema.linha}`
    : problema.arquivo;
}

async function main() {
  const caminhos = caminhosStaged();
  if (caminhos.length === 0) process.exit(0);

  let nomesGuards;
  try {
    nomesGuards = readdirSync(DIR_GUARDS)
      .filter((n) => n.endsWith('.mjs') && !n.startsWith('_'))
      .sort();
  } catch {
    process.exit(0); // sem diretório de guards, nada a fazer
  }

  if (nomesGuards.length === 0) {
    console.log(cinza('guards: nenhum guard em ia/.operations/guards — commit liberado'));
    process.exit(0);
  }

  const arquivos = caminhos.map(criarArquivo);
  const contexto = { arquivos, raiz: RAIZ, git };

  const inicio = Date.now();
  let totalProblemas = 0;
  let houveErro = false;

  console.log(
    cinza(
      `guards: ${nomesGuards.length} guard(s) em ${arquivos.length} arquivo(s) staged`,
    ),
  );

  for (const nomeArquivo of nomesGuards) {
    const caminhoGuard = join(DIR_GUARDS, nomeArquivo);
    let guard;
    try {
      guard = await import(pathToFileURL(caminhoGuard).href);
    } catch (erro) {
      houveErro = true;
      console.error(`\n${vermelho('✗')} ${negrito(nomeArquivo)} ${vermelho('não pôde ser carregado')}`);
      console.error(cinza(`  ${erro.message}`));
      continue;
    }

    const rotulo = guard.nome || nomeArquivo.replace(/\.mjs$/, '');
    if (typeof guard.default !== 'function') {
      houveErro = true;
      console.error(`\n${vermelho('✗')} ${negrito(rotulo)} ${vermelho('não exporta um "export default" função')}`);
      continue;
    }

    let problemas;
    try {
      problemas = normalizarProblemas(await guard.default(contexto));
    } catch (erro) {
      houveErro = true;
      console.error(`\n${vermelho('✗')} ${negrito(rotulo)} ${vermelho('quebrou durante a execução')}`);
      console.error(cinza(`  ${erro.stack || erro.message}`));
      continue;
    }

    if (problemas.length === 0) {
      console.log(`${verde('✓')} ${rotulo}`);
      continue;
    }

    totalProblemas += problemas.length;
    console.error(
      `\n${vermelho('✗')} ${negrito(rotulo)}${guard.descricao ? cinza(` — ${guard.descricao}`) : ''}`,
    );
    for (const problema of problemas) {
      const local = formatarLocal(problema);
      console.error(`  ${vermelho('•')} ${local ? `${negrito(local)} ` : ''}${problema.mensagem}`);
      if (problema.dica) console.error(cinza(`    ↳ ${problema.dica}`));
    }
  }

  const duracao = Date.now() - inicio;

  if (totalProblemas === 0 && !houveErro) {
    console.log(cinza(`guards: ok (${duracao}ms)`));
    process.exit(0);
  }

  const motivo =
    totalProblemas > 0
      ? `${totalProblemas} problema(s) encontrado(s)`
      : 'guard com falha (na dúvida, o runner não libera o commit)';
  console.error(
    `\n${vermelho(negrito('commit abortado'))} — ${motivo} ${cinza(`(${duracao}ms)`)}`,
  );
  console.error(cinza('para pular: SKIP_GUARDS=1 git commit ...  ou  git commit --no-verify'));
  process.exit(1);
}

main().catch((erro) => {
  console.error(vermelho(`guards: falha inesperada no runner\n${erro.stack || erro.message}`));
  process.exit(1);
});
