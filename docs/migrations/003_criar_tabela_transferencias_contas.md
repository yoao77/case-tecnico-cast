# 003 — Criar tabela `transferencias_contas`

É a movimentação: liga a transferência às contas de origem e destino e guarda o valor. É dela que sai o histórico do `GET /contas/{id}/movimentacoes`.

A linha só é criada junto com o débito e o crédito, na mesma transação. Se a transferência falha, essa linha não existe.

## Colunas

| Coluna | Tipo | Regras | Descrição |
| ------ | ---- | ------ | --------- |
| `id` | `INTEGER` | PK | Identificador da movimentação. |
| `transferencia_id` | `BIGINT` | FK `transferencia.id`, único | Transferência a que a movimentação pertence. |
| `conta_origem_id` | `BIGINT` | FK `contas.id` | Conta debitada. |
| `conta_destino_id` | `BIGINT` | FK `contas.id` | Conta creditada. |
| `valor_centavos` | `INTEGER` | obrigatório, `> 0` | Valor transferido em centavos. |
| `criado_em` | `DATETIME` | obrigatório, padrão agora | Momento em que a movimentação foi gravada. |

## Relacionamentos

- `transferencia_id` → `transferencia.id` (1 para 1, garantido pelo `UNIQUE`).
- `conta_origem_id` → `contas.id`.
- `conta_destino_id` → `contas.id`.

## SQL

```sql
CREATE TABLE transferencias_contas (
    id               INTEGER  PRIMARY KEY,
    transferencia_id BIGINT   NOT NULL UNIQUE REFERENCES transferencia (id),
    conta_origem_id  BIGINT   NOT NULL REFERENCES contas (id),
    conta_destino_id BIGINT   NOT NULL REFERENCES contas (id),
    valor_centavos   INTEGER  NOT NULL CHECK (valor_centavos > 0),
    criado_em        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```
