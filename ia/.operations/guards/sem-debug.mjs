/**
 * Sobra de debug não vai para o repositório.
 */

export const nome = 'sem-debug';
export const descricao = 'código de debug esquecido no commit';

const ALVO = /^(src|bin|config|public|tests)\/.+\.(php|js|mjs)$/;
const IGNORADOS = /(^|\/)(vendor|node_modules)\//;
const COMENTARIO = /^\s*(\/\/|#|\*|\/\*)/;

const PADROES = [
  { re: /\bvar_dump\s*\(/, msg: 'var_dump()' },
  { re: /\bprint_r\s*\(/, msg: 'print_r()' },
  { re: /\bdd\s*\(/, msg: 'dd()' },
  { re: /\bbdump\s*\(/, msg: 'bdump() (Tracy)' },
  { re: /Debugger::(dump|barDump)\b/, msg: 'Tracy Debugger' },
  { re: /\bconsole\.(log|debug|table)\s*\(/, msg: 'console.log()' },
  { re: /^\s*debugger\s*;?\s*$/, msg: 'debugger' },
];

export default function ({ arquivos }) {
  const problemas = [];

  for (const arquivo of arquivos) {
    if (!ALVO.test(arquivo.caminho)) continue;
    if (IGNORADOS.test(arquivo.caminho)) continue;

    for (const { numero, texto } of arquivo.linhasAdicionadas()) {
      if (COMENTARIO.test(texto)) continue;

      for (const padrao of PADROES) {
        if (!padrao.re.test(texto)) continue;

        problemas.push({
          arquivo: arquivo.caminho,
          linha: numero,
          mensagem: `${padrao.msg} esquecido: ${texto.trim()}`,
        });
        break;
      }
    }
  }

  return problemas;
}
