<?php
require_once __DIR__ . '/../../includes/funcoes.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
function csrf() { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_campo() { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function csrf_ok() { return hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? ''); }
function exigir_login() { if (empty($_SESSION['admin'])) { header('Location: login.php'); exit; } }
function num_pedido($p) { return 'LV-' . date('Y', strtotime($p['criado_em'])) . '-' . str_pad($p['id'], 6, '0', STR_PAD_LEFT); }
