# 005 — Criar tabela `falhas_processamento`

Guarda os processamentos que falharam, inspirada na tabela `failed_jobs` do Laravel. Hoje a falha esperada é o saldo insuficiente, mas qualquer erro inesperado durante o débito e o crédito também cai aqui, com o detalhe da exceção.

## Colunas

| Nome no Laravel | Coluna | Tipo | Regras | Descrição |
| --------------- | ------ | ---- | ------ | --------- |
| `id` | `id` | `INTEGER` | PK | Identificador da falha. |
| `uuid` | `uuid` | `TEXT` | obrigatório, único | Identificador único do processamento (o mesmo `uuid` do JSON em `dados`). |
| `payload` | `dados` | `LONGTEXT` | obrigatório | Cópia do `dados` do processamento que falhou. |
| `exception` | `excecao` | `LONGTEXT` | obrigatório | Classe, mensagem e rastro da exceção. |
| `failed_at` | `falhou_em` | `DATETIME` | obrigatório, padrão agora | Momento da falha. |

## SQL

```sql
CREATE TABLE falhas_processamento (
    id        INTEGER  PRIMARY KEY,
    uuid      TEXT     NOT NULL UNIQUE,
    dados     LONGTEXT NOT NULL,
    excecao   LONGTEXT NOT NULL,
    falhou_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```
