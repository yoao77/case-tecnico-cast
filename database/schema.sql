DROP TABLE IF EXISTS falhas_processamento;
DROP TABLE IF EXISTS processamentos;
DROP TABLE IF EXISTS transferencias_contas;
DROP TABLE IF EXISTS transferencia;
DROP TABLE IF EXISTS contas;

CREATE TABLE contas (
    id             INTEGER  PRIMARY KEY,
    saldo_centavos INTEGER  NOT NULL CHECK (saldo_centavos >= 0),
    atualizado_em  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE transferencia (
    id           INTEGER  PRIMARY KEY AUTOINCREMENT,
    status       TEXT     NOT NULL CHECK (status IN ('PENDENTE', 'PROCESSANDO', 'CONCLUIDA', 'FALHOU')),
    idempotencia TEXT     UNIQUE,
    criado_em    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE transferencias_contas (
    id               INTEGER  PRIMARY KEY,
    transferencia_id BIGINT   NOT NULL UNIQUE REFERENCES transferencia (id),
    conta_origem_id  BIGINT   NOT NULL REFERENCES contas (id),
    conta_destino_id BIGINT   NOT NULL REFERENCES contas (id),
    valor_centavos   INTEGER  NOT NULL CHECK (valor_centavos > 0),
    criado_em        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE processamentos (
    id            INTEGER  PRIMARY KEY,
    dados         LONGTEXT NOT NULL,
    tentativas    TINYINT  NOT NULL DEFAULT 0,
    reservado_em  INTEGER,
    disponivel_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE falhas_processamento (
    id        INTEGER  PRIMARY KEY,
    uuid      TEXT     NOT NULL UNIQUE,
    dados     LONGTEXT NOT NULL,
    excecao   LONGTEXT NOT NULL,
    falhou_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE contas ADD COLUMN bloqueada BOOLEAN NOT NULL DEFAULT 0 CHECK (bloqueada IN (0, 1));
