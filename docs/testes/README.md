# Testes

Os testes são escritos em [Pest](https://pestphp.com). Os da API (questão 1) ficam em `tests/Feature` e os da questão 2 em `tests/Questao2`.

## Como rodar

Com o contêiner de pé (`docker compose up`):

```bash
docker compose exec api vendor/bin/pest
```

Sem o contêiner no ar:

```bash
docker compose run --rm api sh -c "composer install && vendor/bin/pest"
```

## Como funcionam

### API (`tests/Feature`)

- Cada teste sobe a aplicação Slim de verdade, com rotas, middlewares e tratamento de erro, e faz requisições HTTP direto nela, sem servidor.
- O banco é um SQLite `:memory:` novo a cada teste, já com a conta A (id 1, 100000 centavos) e a conta B (id 2, 50000 centavos). Um teste nunca enxerga o que outro fez.
- Os testes não param no código HTTP: depois de cada operação, eles consultam os saldos e as movimentações pelas próprias rotas da API.
- O log de requisições é trocado por um `NullLogger` para os testes não sujarem `var/logs`.

Os helpers ficam em `tests/Pest.php`. O principal é o `verificarQueNadaMudou()`, que confere se os saldos continuam 100000 e 50000 e se as duas contas seguem sem movimentação. Ele é usado em todo cenário de erro.

### Questão 2 (`tests/Questao2`)

- São testes de unidade, sem Slim e sem banco: o módulo da questão 2 trabalha só em memória.
- Cada teste cria as contas do zero (A com 10000 e, salvo no teste do rollback, B com 5000 centavos) e um `MovimentacaoRepository` vazio.
- Depois de cada transferência, os testes conferem o saldo das duas contas e a lista de movimentações, além da exceção lançada.

## Cenários

### `TransferenciaTest.php`

| Cenário | O que verifica |
| ------- | -------------- |
| Transferência com sucesso | `POST` de 3000 da conta A para a B responde 201 com o ID; saldos ficam A = 97000 e B = 53000; a mesma transferência aparece no histórico das duas contas. |
| Ordem das movimentações | Duas transferências (A→B e B→A) aparecem no histórico da conta A em ordem de ID, como origem e como destino. |
| Saldo insuficiente | 50001 saindo da conta B (que tem 50000) responde 409; saldos e movimentações não mudam. |
| Entrada inválida | 9 variações respondem 400 com a mensagem certa, sem mudar saldos nem movimentações: valor zero, negativo, fracionário (`30.5`), em texto (`"3000"`), origem ausente, destino ausente, valor ausente, corpo vazio e origem igual ao destino. |
| Conta inexistente | Transferência para a conta 99 responde 404; saldos e movimentações não mudam. |

### `ContaTest.php`

| Cenário | O que verifica |
| ------- | -------------- |
| Consulta de saldo | `GET /contas/1` devolve o ID e o saldo atual (100000). |
| Conta inexistente | `GET /contas/99` e `GET /contas/99/movimentacoes` respondem 404. |

### `ProcessamentoTest.php`

Confere a fila por dentro, direto no banco:

| Cenário | O que verifica |
| ------- | -------------- |
| Processamento concluído | A transferência fica `CONCLUIDA`, a fila (`processamentos`) fica vazia e não há falha registrada. |
| Processamento com falha | Com saldo insuficiente, a transferência fica `FALHOU`, a fila fica vazia e `falhas_processamento` guarda os dados do pedido e a `SaldoInsuficienteException`. |

### `Questao2/TransferenciaTest.php`

Os dois primeiros cenários são o mesmo (B bloqueada, 3000 de A para B), rodando no serviço original e no corrigido. É isso que mostra a falha e a correção lado a lado.

| Cenário | O que verifica |
| ------- | -------------- |
| Falha original | No `TransferenciaOriginalService`, lança `ContaBloqueadaException` e termina com A = 7000, B = 5000 e nenhuma movimentação: o dinheiro some. |
| Correção | No `TransferenciaService`, lança a mesma exceção, mas A continua 10000, B continua 5000 e não há movimentação. |
| Rollback | Com B no limite do inteiro (`PHP_INT_MAX`), o crédito estoura com `TypeError` depois do débito, uma falha que a validação não prevê. A transação restaura os saldos (A = 10000, B sem alteração), não registra movimentação e relança o erro. Sem o rollback, A terminaria com 7000. |
| Sucesso com B desbloqueada | Saldos ficam A = 7000 e B = 8000, e a movimentação de 3000 de A para B é registrada. |
| Saldo insuficiente | 10001 saindo da conta A lança `SaldoInsuficienteException`; saldos e movimentações não mudam. |

## O que o case pede x o que cobre

| Pedido no case | Coberto por |
| -------------- | ----------- |
| Teste de sucesso | Transferência com sucesso |
| Teste de saldo insuficiente | Saldo insuficiente |
| Teste de entrada inválida | Entrada inválida (9 variações) |
| Verificar saldos e movimentações, não só o HTTP | Todos os cenários de `TransferenciaTest` |
| Questão 2: teste que demonstra a falha original e a correção | Falha original e Correção |
| Questão 2: sucesso com B desbloqueada | Sucesso com B desbloqueada |
| Questão 2: saldo insuficiente | Saldo insuficiente (`Questao2/TransferenciaTest`) |

No total são 23 casos de teste: 18 da API (contando cada variação de entrada inválida) e 5 da questão 2.
