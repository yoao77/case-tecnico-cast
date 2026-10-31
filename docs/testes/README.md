# Testes

Os testes são escritos em [Pest](https://pestphp.com) e ficam em `tests/Feature`.

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

- Cada teste sobe a aplicação Slim de verdade, com rotas, middlewares e tratamento de erro, e faz requisições HTTP direto nela, sem servidor.
- O banco é um SQLite `:memory:` novo a cada teste, já com a conta A (id 1, 100000 centavos) e a conta B (id 2, 50000 centavos). Um teste nunca enxerga o que outro fez.
- Os testes não param no código HTTP: depois de cada operação, eles consultam os saldos e as movimentações pelas próprias rotas da API.
- O log de requisições é trocado por um `NullLogger` para os testes não sujarem `var/logs`.

Os helpers ficam em `tests/Pest.php`. O principal é o `verificarQueNadaMudou()`, que confere se os saldos continuam 100000 e 50000 e se as duas contas seguem sem movimentação. Ele é usado em todo cenário de erro.

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

## O que o case pede x o que cobre

| Pedido no case | Coberto por |
| -------------- | ----------- |
| Teste de sucesso | Transferência com sucesso |
| Teste de saldo insuficiente | Saldo insuficiente |
| Teste de entrada inválida | Entrada inválida (9 variações) |
| Verificar saldos e movimentações, não só o HTTP | Todos os cenários de `TransferenciaTest` |

No total são 18 casos de teste (contando cada variação de entrada inválida).
