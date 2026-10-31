# 001 — Criar tabela `contas`

Guarda as contas e o saldo atual de cada uma.

## Colunas

| Coluna | Tipo | Regras | Descrição |
| ------ | ---- | ------ | --------- |
| `id` | `INTEGER` | PK | Identificador da conta. |
| `saldo_centavos` | `INTEGER` | obrigatório, `>= 0` | Saldo atual em centavos. |
| `atualizado_em` | `DATETIME` | obrigatório, padrão agora | Última alteração do saldo. |

O `CHECK (saldo_centavos >= 0)` é uma segunda barreira: mesmo que alguma regra da aplicação falhe, o banco não aceita saldo negativo.

## Dados iniciais

| id | Conta | saldo_centavos |
| -- | ----- | -------------- |
| 1 | A | 100000 |
| 2 | B | 50000 |

## SQL

```sql
CREATE TABLE contas (
    id             INTEGER  PRIMARY KEY,
    saldo_centavos INTEGER  NOT NULL CHECK (saldo_centavos >= 0),
    atualizado_em  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO contas (id, saldo_centavos) VALUES
    (1, 100000),
    (2, 50000);
```
