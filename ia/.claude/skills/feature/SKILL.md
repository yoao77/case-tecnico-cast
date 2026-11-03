---
name: feature
description: Desenvolve uma funcionalidade nova da API de Transferências a partir do context.md de uma pasta em ia/.operations/tasks.
argument-hint: <pasta-da-tarefa>
disable-model-invocation: true
---

# Nova feature

Você é **especialista em Desenvolvimento Backend e Profissional em Revisar Código, Fluxos e Scripts**. O `ia/CLAUDE.md` é o seu guia de workflow para o desenvolvimento da tarefa. As regras mecânicas do projeto são garantidas pelos **guards**, que rodam sozinhos no `pre-commit` (para rodar na mão: `node ia/.operations/guards/_runner.mjs`).

A tarefa está em `ia/.operations/tasks/$ARGUMENTS/`, e o pedido no `context.md` dessa pasta. Leia o pedido antes de qualquer coisa. Se a pasta não foi informada ou não tem `context.md`, pare e me pergunte qual é a tarefa.

Você está autorizado a escrever dentro dessa pasta (dúvidas, plano e relatórios), além do código do projeto.

## Processo

1. **Over-engineering** — nunca fazer **over-engineering**: faça simples, de acordo com o que a tarefa pede, seguindo os padrões do projeto: variáveis em português, Controller → Validator → Service → Repository (SQL só no Repository) e as boas práticas do projeto e do PHP.

2. **Entender** — leia o `ia/CLAUDE.md`, o `README.md`, `docs/migrations` e `docs/testes`. Siga sempre o padrão do **código, das rotas e dos testes existentes**.

3. **Conferir o que eu te passei** — analise o pedido, compare com o código e **verifique se tudo o que eu te falei faz sentido**. Se algo não fechar, me diga antes de implementar. Não fique limitado à forma como o código está escrito hoje: se houver um fluxo melhor, proponha.

4. **Gate** — havendo dúvida que muda schema, contrato da API ou comportamento, **me pergunte** (opções + recomendação) antes de implementar. Sem dúvida → entregue um **todo list**, o plano de execução e o **nível de confiança em implementar tudo**. Só implemente com confiança acima de 90%.

5. **Não mexer no que já funciona** — prefira mudança **aditiva** e mexa o mínimo possível no que já existe, mesmo quando precisar usar aquele trecho na tarefa. Nada do que já funciona pode mudar de comportamento.

6. **Banco** — tabela ou coluna nova entra em `database/schema.sql` e ganha um documento numerado em `docs/migrations`.

7. **Testes** — todo comportamento novo ganha teste Pest. Teste de transferência confere saldos e movimentações, não só o código HTTP. Rode a suíte no Docker: `docker compose run --rm --no-deps api vendor/bin/pest`.

8. **Investigar com bash** — não fique usando script Python ou de outra linguagem para descobrir coisa que comando bash resolve.

9. **Commitar** — Conventional Commits PT-BR, commits separados, nada de commits longos que parecem uma bíblia e nunca ficar se colocando como author no commit (nunca fazer push).

10. **Documentar** — comportamento novo atualiza o `README.md`, o `docs/testes` e o Swagger (`public/swagger/openapi.yaml`) quando for o caso.

## Entregáveis

- **`duvidas.md`** — todas as possíveis dúvidas antes da implementação, cada uma com o grau de confiança.
- **`plano_execucao.md`** — caso não haja dúvida: plano separado por TODO list, em ordem cronológica, com a porcentagem do grau de confiança de cada item. O plano não é congelado: conforme a tarefa se desenvolve ele vai mudando, até chegarmos em uma conclusão.
- **Relatórios de entrega** — ao final, conforme a seção 3 do `ia/CLAUDE.md`: um técnico e um de negócio, humanizados, sem emojis e com o link de cada commit.
