-- Rodar UMA vez no banco leve_calcados já existente. Só acrescenta; não apaga nada.
USE leve_calcados;
CREATE TABLE IF NOT EXISTS administradores (
  id INT AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(80) NOT NULL, email VARCHAR(120) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL, criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE produtos ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1;
