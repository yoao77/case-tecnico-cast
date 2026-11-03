# ia

> `ia/` é o diretório do meu fluxo de desenvolvimento com IA (Claude Code) nesta API.

Ele guarda as regras que o Claude segue no projeto, as skills que eu chamo e o
registro de cada tarefa, do pedido à entrega. **Os caminhos deste documento são
relativos à raiz do repositório.**

O Claude Code só carregaria o que está aqui depois de ler algum arquivo de
`ia/`. Por isso a raiz tem duas pontes, e o fluxo vale desde o início da sessão:

- o `CLAUDE.md` da raiz importa este arquivo (`@ia/CLAUDE.md`);
- `.claude/skills/` tem um link para cada skill de `ia/.claude/skills/`.

| diretório           | papel                                                    |
| ------------------- | -------------------------------------------------------- |
| `ia/.claude/skills/` | as skills do projeto                                     |
| `ia/.operations/`    | o ciclo de uma tarefa: pedido, dúvidas, plano, execução e entrega |
| `ia/.env`            | as variáveis do fluxo (ex.: a base do link de commit)    |

Dentro de `ia/.operations/`:

| diretório                     | papel                                                 |
| ----------------------------- | ----------------------------------------------------- |
| `ia/.operations/tasks/`       | o trabalho de cada funcionalidade nova                |
| `ia/.operations/maintenance/` | bugs e refatorações — manutenção do que já existe     |
| `ia/.operations/guards/`      | guards executados automaticamente antes de cada commit |

---

## 1. O projeto

A documentação do projeto mora no próprio repositório e é o ponto de partida
antes de mexer em qualquer coisa:

| onde                          | o que tem                                                         |
| ----------------------------- | ----------------------------------------------------------------- |
| `README.md`                   | como rodar, como a transferência é processada, escolhas técnicas, idempotência e concorrência, questão 2 |
| `docs/migrations/`            | as tabelas, colunas e relacionamentos                             |
| `docs/testes/`                | os cenários de teste                                              |
| `public/swagger/openapi.yaml` | o contrato da API                                                 |

### 1.1. Regras do código

- **Simples.** Nada de over-engineering: resolver o que a tarefa pede, do jeito
  que o código já faz.
- **Camadas.** Controller → Validator → Service → Repository. O controller
  recebe e responde, o validator cuida da forma da entrada, o service tem as
  regras e o repository fala com o banco. SQL só no Repository, com PDO puro.
- **Português.** Classes, métodos e variáveis em português.
- **Dinheiro em centavos.** Sempre `int`, nunca float.
- **Erros como exceções.** Cada regra quebrada lança uma exceção de
  `src/Exceptions` com o status HTTP, e o `ErroHandler` monta a resposta.
- **Comentário só em lógica complexa**, explicando o porquê.
- **Testes em Pest**, rodando no Docker:
  `docker compose run --rm --no-deps api vendor/bin/pest`. Teste de
  transferência confere saldos e movimentações, não só o código HTTP.
- **Banco.** Tabela ou coluna nova entra em `database/schema.sql` e ganha um
  documento numerado em `docs/migrations/`.
- **Documentação junto.** Comportamento novo atualiza o `README.md`, o
  `docs/testes` e o Swagger quando for o caso.

### 1.2. Commits

- Conventional Commits em português, **uma linha**, sem corpo e sem acento
  (ex.: `feat: adiciona o painel de demonstracao`).
- Um assunto por commit. Ajuste no mesmo assunto, antes do push, entra por
  `amend`/`fixup` no commit dele — não vira commit novo.
- **Nunca** colocar o Claude como autor ou coautor e **nunca** fazer push: o
  push é meu.

## 2. `ia/.claude/skills/` — as skills

| comando                 | quando usar                                                  |
| ----------------------- | ------------------------------------------------------------ |
| `/feature <pasta>`      | funcionalidade nova, a partir de `ia/.operations/tasks/<pasta>/context.md` |
| `/manutencao <pasta>`   | bug ou refatoração, a partir de `ia/.operations/maintenance/{bugs,refatoracoes}/<pasta>/context.md` |
| `/grilling`, `/grill-me` | sabatinar um plano ou uma decisão antes de implementar, uma rodada de perguntas por vez |

`feature` e `manutencao` só rodam quando eu chamo (`disable-model-invocation`):
são elas que autorizam o Claude a escrever na pasta da tarefa. As de
_grilling_ vêm de [mattpocock/skills](https://github.com/mattpocock/skills), com
a versão registrada em `ia/.claude/skills-lock.json`. O `grill-with-docs`
depende de uma skill `domain-modeling` que não está no projeto, por isso fica
sem link na raiz.

Uma pasta por skill, e dentro dela um `SKILL.md`. O **nome da pasta** é o
comando, e o `argument-hint` mostra o que vem depois dele; no texto da skill, o
`$ARGUMENTS` é trocado pelo que eu digitei.

```markdown
---
name: feature
description: Quando usar esta skill, em uma linha.
argument-hint: <pasta-da-tarefa>
disable-model-invocation: true
---

As instruções da skill. A tarefa está em ia/.operations/tasks/$ARGUMENTS/.
```

Skill nova: crie a pasta aqui e o link na raiz.

```sh
ln -s ../../ia/.claude/skills/<nome> .claude/skills/<nome>
```

## 3. `ia/.operations/tasks/` — o fluxo de uma tarefa

1. Eu crio a pasta da tarefa e escrevo o pedido no `context.md`.
2. Chamo `/feature <pasta>`.
3. O Claude lê o pedido, confere com o código e:
   - se houver dúvida que muda schema, contrato da API ou comportamento, ela vai
     para o `duvidas.md` e é perguntada **antes** de implementar, com opções e
     recomendação;
   - sem dúvida, ele escreve o `plano_execucao.md` e implementa, só com
     confiança acima de 90%.
4. Testes passando e commits como na seção 1.2.
5. A tarefa fecha com os dois relatórios de entrega.

As pastas `tasks/0001-login` e `maintenance/bugs/0001-transacao-falha` são
exemplos da estrutura.

### 3.1. Nome da pasta — `NNNN-nome-curto`

```
<numero>-<nome-curto-da-tarefa>
```

- **número sequencial** de quatro dígitos, contado dentro do próprio diretório
  (`0001`, `0002`, …);
- nome em **kebab-case**, minúsculo, sem acento e sem caractere especial;
- **2 a 4 palavras**, o suficiente para bater o olho e saber qual é qual;
- descreve **a tarefa**, não a solução técnica.

> **Por que:** o número mantém a ordem em que as tarefas chegaram, e o nome
> deixa achar uma sem abrir todas.

### 3.2. Estrutura da pasta

```
ia/.operations/
└── tasks/
    └── <NNNN-nome-curto>/
        ├── context.md              o pedido, escrito por mim
        ├── duvidas.md              (se houver)
        ├── plano_execucao.md
        └── relatorio-entrega/
            ├── RELATORIO_ENTREGA.md
            └── RELATORIO_ENTREGA_NEGOCIO.md
```

**`context.md`** — o único arquivo que eu escrevo: o que precisa ser feito, por
que, e como sei que ficou pronto. Pode ser curto; o que faltar vira dúvida.

**`duvidas.md`** — as dúvidas levantadas antes da implementação, cada uma
sempre com um **grau de confiança**.

**`plano_execucao.md`** — o plano separado por TODO list, em ordem cronológica,
com a porcentagem do grau de confiança de cada item. Ele **não é congelado**:
conforme a tarefa se desenvolve, o plano vai mudando até chegarmos em uma
conclusão.

**`RELATORIO_ENTREGA.md`** — o relatório técnico, para quem revisa o código: o
que mudou, as decisões tomadas, os arquivos tocados, os testes e os commits.

**`RELATORIO_ENTREGA_NEGOCIO.md`** — o relatório para quem acompanha o produto
(pode ir na descrição do pull request): o que muda para quem usa a API, sem
detalhe de código.

> Em ambos pode haver outras seções, mas **"Escopo entregue"** e **"Cenário de
> testes"** são as importantes e não podem faltar.

### 3.3. Commits da tarefa

Cada item do plano que entrega algo vira **um commit**, na ordem do plano, e o
relatório mostra o commit ao lado do item que ele entregou. Quem lê não precisa
cruzar uma lista de commits com uma lista de itens.

Se um item não couber em um commit só, isso é sinal de que **ele está grande
demais**: pare antes de commitar e me proponha a divisão no plano.

### 3.4. Como escrever os relatórios

- **Humanizado**: uma explicação normal, que alguém que não acompanhou a
  implementação consiga seguir. O critério é simples: não pode ser difícil de
  entender.
- **Sem emojis**, em nenhum dos dois relatórios.
- **Commit com link**: todo commit apresentado vem com o hash curto, o assunto e,
  na linha de baixo, o link direto para ele no GitHub.

```markdown
**Commit.** `861c56d` — feat: adiciona o painel de demonstracao
https://github.com/yoao77/case-tecnico-cast/commit/861c56d28b122fa69f4997f3c289682bac8848d9
```

O link é `GIT_COMMIT_BASE_URL` (do `ia/.env`, seção 6) seguido do **hash
completo** — `git rev-parse <hash-curto>`. Ele só abre depois do push, e commit
reescrito (`amend`, `rebase`) ganha hash novo: o link tem de ser refeito com ele.

## 4. `ia/.operations/maintenance/` — manutenção do que já existe

Enquanto `ia/.operations/tasks/` guarda o desenvolvimento de coisa nova,
`ia/.operations/maintenance/` guarda o trabalho em cima do que **já existe**.

```
ia/.operations/
└── maintenance/
    ├── bugs/            correção de defeito
    └── refatoracoes/    melhoria de código sem mudar comportamento
```

O nome da pasta segue a seção 3.1, com a numeração de cada diretório (ex.:
`bugs/0001-transacao-falha`), e o conteúdo segue a estrutura da seção 3.2. A
skill é a `/manutencao <pasta>`. O que muda é o que entra junto:

- **bug**: primeiro um teste que reproduz o defeito e falha; depois a **causa
  raiz** (não só o sintoma), a correção mínima e o roteiro de testes que comprova
  que ela resolve. O relatório fecha pela causa raiz;
- **refatoração**: o antes e o depois, e a garantia de que o comportamento não
  mudou — a suíte inteira passa antes e depois.

## 5. `ia/.operations/guards/` — guards de IA

Guards da aplicação, executados antes de qualquer commit. O hook
`.git/hooks/pre-commit` chama o `_runner.mjs`, que executa todo `*.mjs` do
diretório. Se algum guard acusar problema, o commit é abortado.

| guard                  | cobra                                                     |
| ---------------------- | --------------------------------------------------------- |
| `sem-debug`            | `var_dump`, `print_r`, `dd`, `console.log` e afins esquecidos |
| `sem-html-no-php`      | HTML/CSS montado em Controller, Service ou Repository     |
| `sql-so-no-repository` | consulta ao banco fora do Repository                      |

Eles rodam no Node da máquina, fora do Docker. O hook não é versionado e é
instalado uma vez por clone:

```sh
printf '#!/bin/sh\nexec node ia/.operations/guards/_runner.mjs\n' > .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

O contrato para escrever um guard, como rodar na mão e como pular estão em
`ia/.operations/guards/README.md`.

## 6. `ia/.env` — as variáveis do fluxo

Valores que o fluxo usa e que não pertencem a nenhuma tarefa. Formato
`CHAVE=valor`, uma por linha, com comentário `#` acima de cada uma.

| variável              | uso                                                       |
| --------------------- | --------------------------------------------------------- |
| `GIT_COMMIT_BASE_URL` | base do link de commit no GitHub, usada nos relatórios de entrega (seção 3.4) |

Valor novo entra aqui, e não escrito à mão nos documentos: se a URL mudar, basta
trocar em um lugar. O arquivo é versionado porque só guarda o que é público;
segredo nunca entra aqui.
