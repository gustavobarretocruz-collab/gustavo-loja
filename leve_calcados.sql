-- Leve Calçados: banco novo e independente. ATENÇÃO: rodar de novo apaga e recria as tabelas.
CREATE DATABASE IF NOT EXISTS leve_calcados CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE leve_calcados;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS administradores, itens_pedido, pedidos, enderecos, clientes, produto_tamanhos, produtos, categorias, marcas;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE marcas (id INT AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(40) NOT NULL UNIQUE, slug VARCHAR(40) NOT NULL UNIQUE);
CREATE TABLE categorias (id INT AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(40) NOT NULL UNIQUE);
CREATE TABLE produtos (
  id INT AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(120) NOT NULL, slug VARCHAR(120) NOT NULL UNIQUE,
  marca_id INT NOT NULL, categoria_id INT NOT NULL, descricao TEXT, preco DECIMAL(10,2) NOT NULL,
  cor VARCHAR(30), destaque TINYINT(1) NOT NULL DEFAULT 0, lancamento TINYINT(1) NOT NULL DEFAULT 0, ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (marca_id) REFERENCES marcas(id), FOREIGN KEY (categoria_id) REFERENCES categorias(id),
  INDEX (preco), INDEX (criado_em)
);
CREATE TABLE produto_tamanhos (
  id INT AUTO_INCREMENT PRIMARY KEY, produto_id INT NOT NULL, numero TINYINT NOT NULL, estoque INT NOT NULL DEFAULT 0,
  UNIQUE (produto_id, numero), FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
);
CREATE TABLE clientes (
  id INT AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(120) NOT NULL, cpf VARCHAR(14), email VARCHAR(120) NOT NULL UNIQUE,
  telefone VARCHAR(20), senha_hash VARCHAR(255) NULL, conta_desde DATETIME NULL, criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE enderecos (
  id INT AUTO_INCREMENT PRIMARY KEY, cliente_id INT NOT NULL, cep VARCHAR(9) NOT NULL, estado CHAR(2) NOT NULL,
  cidade VARCHAR(80) NOT NULL, bairro VARCHAR(80) NOT NULL, rua VARCHAR(120) NOT NULL, numero VARCHAR(10) NOT NULL,
  complemento VARCHAR(60), criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
);
CREATE TABLE pedidos (
  id INT AUTO_INCREMENT PRIMARY KEY, cliente_id INT NOT NULL, endereco_id INT NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL, desconto DECIMAL(10,2) NOT NULL DEFAULT 0, total DECIMAL(10,2) NOT NULL, cupom VARCHAR(30),
  pagamento ENUM('pix','debito','credito') NOT NULL, parcelas TINYINT NOT NULL DEFAULT 1,
  status VARCHAR(30) NOT NULL DEFAULT 'pago (demonstração)', criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cliente_id) REFERENCES clientes(id), FOREIGN KEY (endereco_id) REFERENCES enderecos(id), INDEX (criado_em)
);
CREATE TABLE administradores (
  id INT AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(80) NOT NULL, email VARCHAR(120) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL, criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE itens_pedido (
  id INT AUTO_INCREMENT PRIMARY KEY, pedido_id INT NOT NULL, produto_id INT NOT NULL, tamanho TINYINT NOT NULL,
  quantidade INT NOT NULL, preco_unitario DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE, FOREIGN KEY (produto_id) REFERENCES produtos(id)
);

INSERT INTO marcas (nome, slug) VALUES ('Nike','nike'),('Adidas','adidas'),('Puma','puma'),('ASICS','asics'),('New Balance','new-balance');
INSERT INTO categorias (nome) VALUES ('Corrida'),('Casual'),('Lifestyle');

INSERT INTO produtos (nome, slug, marca_id, categoria_id, descricao, preco, cor, destaque, lancamento) VALUES
('Nike Air Max 90','nike-air-max-90',(SELECT id FROM marcas WHERE slug='nike'),(SELECT id FROM categorias WHERE nome='Lifestyle'),'Clássico dos anos 90 com a unidade Air visível no calcanhar e visual retrô para o dia a dia.',899.99,'branco',1,0),
('Nike Air Force 1 07','nike-air-force-1-07',(SELECT id FROM marcas WHERE slug='nike'),(SELECT id FROM categorias WHERE nome='Casual'),'Ícone do basquete que virou febre nas ruas, com cabedal em couro e solado de borracha resistente.',799.99,'branco',1,0),
('Nike Pegasus 41','nike-pegasus-41',(SELECT id FROM marcas WHERE slug='nike'),(SELECT id FROM categorias WHERE nome='Corrida'),'Tênis de corrida versátil, pensado para treinos diários com amortecimento responsivo.',999.99,'preto',0,1),
('Nike Revolution 7','nike-revolution-7',(SELECT id FROM marcas WHERE slug='nike'),(SELECT id FROM categorias WHERE nome='Corrida'),'Leve e confortável, ótima opção para caminhadas e corridas leves.',349.99,'cinza',0,0),
('Adidas Ultraboost Light','adidas-ultraboost-light',(SELECT id FROM marcas WHERE slug='adidas'),(SELECT id FROM categorias WHERE nome='Corrida'),'Corrida com amortecimento Boost, macio e responsivo para longas distâncias.',1099.99,'preto',1,1),
('Adidas Samba OG','adidas-samba-og',(SELECT id FROM marcas WHERE slug='adidas'),(SELECT id FROM categorias WHERE nome='Lifestyle'),'Modelo clássico de visual vintage, em couro com detalhes em camurça.',699.99,'branco',1,0),
('Adidas Superstar','adidas-superstar',(SELECT id FROM marcas WHERE slug='adidas'),(SELECT id FROM categorias WHERE nome='Casual'),'O famoso bico de concha em couro, um clássico atemporal.',549.99,'branco',0,0),
('Adidas Duramo SL','adidas-duramo-sl',(SELECT id FROM marcas WHERE slug='adidas'),(SELECT id FROM categorias WHERE nome='Corrida'),'Leve e acessível, ideal para treinos e uso diário.',299.99,'azul',0,1),
('Puma Suede Classic XXI','puma-suede-classic-xxi',(SELECT id FROM marcas WHERE slug='puma'),(SELECT id FROM categorias WHERE nome='Lifestyle'),'Camurça icônica com a faixa lateral característica, visual urbano e atemporal.',449.99,'azul',1,0),
('Puma RS-X','puma-rs-x',(SELECT id FROM marcas WHERE slug='puma'),(SELECT id FROM categorias WHERE nome='Lifestyle'),'Visual volumoso retrô-futurista com amortecimento confortável.',599.99,'branco',0,1),
('Puma Softride Pro','puma-softride-pro',(SELECT id FROM marcas WHERE slug='puma'),(SELECT id FROM categorias WHERE nome='Corrida'),'Espuma macia para conforto no uso diário e em caminhadas.',299.99,'preto',0,0),
('Puma Caven 2.0','puma-caven-2-0',(SELECT id FROM marcas WHERE slug='puma'),(SELECT id FROM categorias WHERE nome='Casual'),'Estilo casual esportivo com ótimo custo-benefício.',349.99,'vermelho',0,0),
('ASICS Gel-Kayano 31','asics-gel-kayano-31',(SELECT id FROM marcas WHERE slug='asics'),(SELECT id FROM categorias WHERE nome='Corrida'),'Corrida com estabilidade e amortecimento GEL, indicado para quem pisa para dentro.',1499.99,'azul',1,1),
('ASICS Gel-Nimbus 26','asics-gel-nimbus-26',(SELECT id FROM marcas WHERE slug='asics'),(SELECT id FROM categorias WHERE nome='Corrida'),'Máximo amortecimento para longas corridas, com conforto extra.',1399.99,'cinza',0,0),
('ASICS Gel-1130','asics-gel-1130',(SELECT id FROM marcas WHERE slug='asics'),(SELECT id FROM categorias WHERE nome='Lifestyle'),'Estilo retrô de corrida dos anos 2000, muito usado no dia a dia.',749.99,'branco',0,1),
('ASICS GT-1000 13','asics-gt-1000-13',(SELECT id FROM marcas WHERE slug='asics'),(SELECT id FROM categorias WHERE nome='Corrida'),'Estabilidade e amortecimento equilibrados para corridas regulares.',599.99,'preto',0,0),
('New Balance 574','new-balance-574',(SELECT id FROM marcas WHERE slug='new-balance'),(SELECT id FROM categorias WHERE nome='Lifestyle'),'Clássico atemporal, camurça e malha com silhueta inconfundível.',599.99,'cinza',1,0),
('New Balance 530','new-balance-530',(SELECT id FROM marcas WHERE slug='new-balance'),(SELECT id FROM categorias WHERE nome='Lifestyle'),'Inspirado nos tênis de corrida dos anos 2000, com visual retrô e muito conforto.',699.99,'branco',1,1),
('New Balance 990v6','new-balance-990v6',(SELECT id FROM marcas WHERE slug='new-balance'),(SELECT id FROM categorias WHERE nome='Lifestyle'),'Linha premium com acabamento de alta qualidade e conforto superior.',1799.99,'cinza',0,0),
('New Balance Fresh Foam 680','new-balance-fresh-foam-680',(SELECT id FROM marcas WHERE slug='new-balance'),(SELECT id FROM categorias WHERE nome='Corrida'),'Amortecimento macio para treinos e rotina, leve e confortável.',449.99,'azul',0,1);

-- numerações 38 a 44 para todos os modelos (estoque de exemplo; alguns números começam esgotados)
INSERT INTO produto_tamanhos (produto_id, numero, estoque)
SELECT p.id, n.numero, (p.id * n.numero) % 6 FROM produtos p
CROSS JOIN (SELECT 38 numero UNION SELECT 39 UNION SELECT 40 UNION SELECT 41 UNION SELECT 42 UNION SELECT 43 UNION SELECT 44) n;
