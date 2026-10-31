# 002 — Criar tabela `transferencia`

Guarda cada pedido de transferência e em que etapa ele está. O `id` dessa tabela é o ID devolvido no `POST /transferencias`.

## Colunas

| Coluna | Tipo | Regras | Descrição |
| ------ | ---- | ------ | --------- |
| `id` | `INTEGER` | PK, `AUTOINCREMENT` | ID único da transferência. O `AUTOINCREMENT` garante que um ID nunca é reaproveitado. |
| `status` | `TEXT` | obrigatório, `PENDENTE`, `PROCESSANDO`, `CONCLUIDA` ou `FALHOU` | Etapa do processamento. |
| `idempotencia` | `TEXT` | único, opcional | Chave enviada pelo cliente no cabeçalho `Idempotency-Key`. Fica vazia enquanto a idempotência estiver desativada. |
| `criado_em` | `DATETIME` | obrigatório, padrão agora | Momento em que o pedido chegou. |

## Ciclo do status

```
PENDENTE ──> PROCESSANDO ──> CONCLUIDA
                         └─> FALHOU
```

- `PENDENTE`: pedido aceito e colocado na fila.
- `PROCESSANDO`: processamento reservado, débito e crédito em andamento.
- `CONCLUIDA`: débito, crédito e movimentação gravados.
- `FALHOU`: nada foi alterado nos saldos; o motivo fica em `falhas_processamento`.

## Relacionamentos

- Tem no máximo uma linha em `transferencias_contas`.

## SQL

```sql
CREATE TABLE transferencia (
    id           INTEGER  PRIMARY KEY AUTOINCREMENT,
    status       TEXT     NOT NULL CHECK (status IN ('PENDENTE', 'PROCESSANDO', 'CONCLUIDA', 'FALHOU')),
    idempotencia TEXT     UNIQUE,
    criado_em    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```
