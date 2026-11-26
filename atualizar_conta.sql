-- Rodar UMA vez no banco leve_calcados existente. Só acrescenta colunas; não apaga nada.
USE leve_calcados;
ALTER TABLE clientes ADD COLUMN senha_hash VARCHAR(255) NULL, ADD COLUMN conta_desde DATETIME NULL;
ALTER TABLE enderecos ADD COLUMN criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
