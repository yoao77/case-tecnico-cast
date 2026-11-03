# ia/.operations/guards

Guards executados automaticamente pelo hook `.git/hooks/pre-commit`.
Se qualquer guard retornar problemas, o commit é abortado.

## Como funciona

`_runner.mjs` descobre todo `*.mjs` deste diretório (arquivos com `_` na frente
são ignorados — são infraestrutura, não guard), importa cada um e chama o
`export default` passando o contexto do commit.

Roda no Node da máquina, fora do Docker: o contêiner da API só tem PHP.

## Instalando o hook

O hook fica em `.git/hooks/`, que não é versionado, então é instalado uma vez
por clone:

```sh
printf '#!/bin/sh\nexec node ia/.operations/guards/_runner.mjs\n' > .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

## Escrevendo um guard

Crie um `.mjs` aqui. Só isso — nada para registrar em lugar nenhum.

```js
export const nome = 'meu-guard';           // opcional (default: nome do arquivo)
export const descricao = 'o que ele cobra'; // opcional, aparece no erro

export default function ({ arquivos, raiz, git }) {
  const problemas = [];

  for (const arquivo of arquivos) {
    if (!arquivo.caminho.endsWith('.php')) continue;

    for (const { numero, texto } of arquivo.linhasAdicionadas()) {
      if (texto.includes('coisa proibida')) {
        problemas.push({
          arquivo: arquivo.caminho,
          linha: numero,
          mensagem: 'não faça isso',
          dica: 'faça assim',       // opcional
        });
      }
    }
  }

  return problemas; // vazio = passou
}
```

Pode ser `async`. Pode retornar uma string simples (`return 'mensagem'`) quando
o problema não é de um arquivo específico.

## Contexto recebido

| campo      | o que é                                          |
| ---------- | ------------------------------------------------ |
| `arquivos` | arquivos staged (adicionados/modificados/movidos) |
| `raiz`     | caminho absoluto da raiz do repositório           |
| `git`      | `git(['args'])` → executa git e devolve a saída   |

Cada `arquivo`:

| membro                  | o que devolve                                        |
| ----------------------- | ---------------------------------------------------- |
| `.caminho`              | caminho relativo à raiz                              |
| `.ext`                  | extensão em minúsculo (`.php`)                        |
| `.linhasAdicionadas()`  | **só as linhas novas** deste commit: `[{numero, texto}]` |
| `.linhas()`             | todas as linhas do arquivo: `[{numero, texto}]`       |
| `.conteudo()`           | conteúdo completo como string                        |
| `.ehBinario()`          | `true` para imagem/pdf/etc                            |

**Prefira `linhasAdicionadas()`.** Ela cobra só o que você está commitando —
código que você não encostou não derruba o commit. Use `conteudo()` apenas
quando o guard precisa mesmo olhar o arquivo inteiro (ex.: checar se falta um
`declare(strict_types=1)`).

Tudo é lido do **index**, não do working tree: o que o guard vê é exatamente o
que vai ser commitado, mesmo com mudanças não-staged no disco.

## Guards atuais

| guard                  | cobra                                                              |
| ---------------------- | ------------------------------------------------------------------ |
| `sem-debug`            | sem `var_dump`, `print_r`, `dd`, `console.log`, `debugger` em `src`, `bin`, `config`, `public` e `tests` |
| `sem-html-no-php`      | sem HTML/CSS montado em Controller, Service ou Repository — vai no template Twig |
| `sql-so-no-repository` | sem `prepare`, `query` ou `exec` fora de `src/Repository` e `src/Database` |

## Pular os guards

```sh
SKIP_GUARDS=1 git commit -m "..."
git commit --no-verify -m "..."
```

## Rodar sem commitar

```sh
node ia/.operations/guards/_runner.mjs
```

Roda contra o que estiver staged no momento.
