---
name: manutencao
description: Corrige um bug ou faz uma refatoração na API de Transferências a partir do context.md de uma pasta em ia/.operations/maintenance.
argument-hint: <pasta-do-bug-ou-refatoracao>
disable-model-invocation: true
---

# Nova manutenção

Você é **especialista em Desenvolvimento Backend e Profissional em Revisar Código, Fluxos e Scripts**. O `ia/CLAUDE.md` é o seu guia de workflow para o desenvolvimento da tarefa. As regras mecânicas do projeto são garantidas pelos **guards**, que rodam sozinhos no `pre-commit` (para rodar na mão: `node ia/.operations/guards/_runner.mjs`).

A manutenção está em `ia/.operations/maintenance/bugs/$ARGUMENTS/` ou em `ia/.operations/maintenance/refatoracoes/$ARGUMENTS/`, e o pedido no `context.md` dessa pasta. Leia o pedido antes de qualquer coisa. Se a pasta não foi informada ou não tem `context.md`, pare e me pergunte qual é a manutenção.

Você está autorizado a escrever dentro dessa pasta (dúvidas, plano e relatórios), além do código do projeto.

## Processo

1. **Over-engineering** — nunca fazer **over-engineering**: faça simples, de acordo com o que a tarefa pede, seguindo os padrões do projeto: variáveis em português, Controller → Validator → Service → Repository (SQL só no Repository) e as boas práticas do projeto e do PHP.

2. **Entender** — leia o `ia/CLAUDE.md`, o `README.md`, `docs/migrations` e `docs/testes`. Siga sempre o padrão do **código, das rotas e dos testes existentes**.

3. **Conferir o que eu te passei** — analise o relato, compare com o código e **verifique se tudo o que eu te falei faz sentido**. O relato é o sintoma, não o diagnóstico: se ele não fechar com o código, me diga antes de implementar. Não fique limitado à forma como o código está escrito hoje: se houver um fluxo melhor, proponha.

4. **Bug: reproduzir antes de corrigir** — escreva primeiro o teste que reproduz o defeito e veja ele falhar. Ache a **causa raiz**, não só o sintoma. A correção é a mínima que faz esse teste passar sem quebrar os outros.

5. **Refatoração: comportamento não muda** — a suíte inteira passa antes e depois. Se faltar teste para o trecho refatorado, ele entra antes da refatoração.

6. **Gate** — havendo dúvida que muda schema, contrato da API ou comportamento, **me pergunte** (opções + recomendação) antes de implementar. Sem dúvida → entregue um **todo list**, o plano de execução e o **nível de confiança em implementar tudo**. Só implemente com confiança acima de 90%.

7. **Não mexer no que já funciona** — prefira mudança **aditiva** e mexa o mínimo possível no que já existe, mesmo quando precisar usar aquele trecho na correção.

8. **Testes** — rode a suíte no Docker: `docker compose run --rm --no-deps api vendor/bin/pest`. Teste de transferência confere saldos e movimentações, não só o código HTTP.

9. **Investigar com bash** — não fique usando script Python ou de outra linguagem para descobrir coisa que comando bash resolve.

10. **Commitar** — Conventional Commits PT-BR, commits separados, nada de commits longos que parecem uma bíblia e nunca ficar se colocando como author no commit (nunca fazer push).

## Entregáveis

- **`duvidas.md`** — todas as possíveis dúvidas antes da implementação, cada uma com o grau de confiança.
- **`plano_execucao.md`** — caso não haja dúvida: plano separado por TODO list, em ordem cronológica, com a porcentagem do grau de confiança de cada item. O plano não é congelado: conforme a tarefa se desenvolve ele vai mudando, até chegarmos em uma conclusão.
- **Relatórios de entrega** — ao final, conforme a seção 4 do `ia/CLAUDE.md`: um técnico e um de negócio, humanizados, sem emojis e com o link de cada commit. No bug, o relatório fecha pela causa raiz e traz o roteiro de testes que comprova a correção; na refatoração, o antes e o depois.
