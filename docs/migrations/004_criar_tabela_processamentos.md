# 004 — Criar tabela `processamentos`

É a fila, inspirada na tabela `jobs` do Laravel. Cada transferência entra aqui como um processamento e sai quando termina: apagada se deu certo, movida para `falhas_processamento` se falhou.

Nesta versão, o processamento acontece dentro da própria requisição (o case pede a resposta 201 ou 409 na hora). Se o processo cair no meio, o worker (`bin/worker.sh`) retoma os processamentos com a reserva vencida há mais de 30 segundos e desiste depois de 3 tentativas.

## Colunas

| Nome no Laravel | Coluna | Tipo | Regras | Descrição |
| --------------- | ------ | ---- | ------ | --------- |
| `id` | `id` | `INTEGER` | PK | Identificador do processamento. |
| `payload` | `dados` | `LONGTEXT` | obrigatório | JSON com `uuid`, `transferenciaId`, `origem`, `destino` e `valorCentavos`. |
| `attempts` | `tentativas` | `TINYINT` | obrigatório, padrão 0 | Quantas vezes o processamento foi reservado. Passou de 3, vai para `falhas_processamento`. |
| `reserved_at` | `reservado_em` | `INTEGER` | opcional | Timestamp Unix de quando foi pego para execução. Mais velho que 30 segundos, a reserva vence e o worker pode pegar de novo. |
| `available_at` | `disponivel_em` | `DATETIME` | obrigatório, padrão agora | A partir de quando pode ser executado. |
| `created_at` | `criado_em` | `DATETIME` | obrigatório, padrão agora | Momento em que entrou na fila. |

## Exemplo de `dados`

```json
{
  "uuid": "27408d58-9908-44f5-8c13-01e3a252a65a",
  "transferenciaId": 1,
  "origem": 1,
  "destino": 2,
  "valorCentavos": 3000
}
```

## SQL

```sql
CREATE TABLE processamentos (
    id            INTEGER  PRIMARY KEY,
    dados         LONGTEXT NOT NULL,
    tentativas    TINYINT  NOT NULL DEFAULT 0,
    reservado_em  INTEGER,
    disponivel_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```
