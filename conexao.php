<?php
// Ajuste usuário/senha conforme o seu MySQL (XAMPP: root / senha vazia)
$cfg = ['host' => 'localhost', 'banco' => 'leve_calcados', 'usuario' => 'root', 'senha' => ''];
try {
    $pdo = new PDO("mysql:host={$cfg['host']};dbname={$cfg['banco']};charset=utf8mb4", $cfg['usuario'], $cfg['senha'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (PDOException $e) {
    die('Erro de conexão com o banco: ' . $e->getMessage());
}
