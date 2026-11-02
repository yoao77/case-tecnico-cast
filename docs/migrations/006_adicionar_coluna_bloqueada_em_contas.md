# 006 — Adicionar coluna `bloqueada` em `contas`

Chegou com a questão 2 do case: marca a conta que está bloqueada e não pode receber transferências.

## Coluna

| Coluna | Tipo | Regras | Descrição |
| ------ | ---- | ------ | --------- |
| `bloqueada` | `BOOLEAN` | obrigatório, `0` ou `1`, padrão `0` | `1` quando a conta está bloqueada. |

As contas iniciais continuam desbloqueadas (`0`). A API da questão 1 não lê essa coluna, então rotas, saldos e respostas continuam iguais. O módulo da questão 2 trabalha em memória e usa a mesma flag na entidade `Conta` (`src/Questao2/Entity/Conta.php`).

## SQL

```sql
ALTER TABLE contas ADD COLUMN bloqueada BOOLEAN NOT NULL DEFAULT 0 CHECK (bloqueada IN (0, 1));
```
