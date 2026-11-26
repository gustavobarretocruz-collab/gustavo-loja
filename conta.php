<?php
require_once __DIR__ . '/funcoes.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function cliente_logado() { return $_SESSION['cliente'] ?? null; }
function csrf_c() { return $_SESSION['csrf_c'] ??= bin2hex(random_bytes(16)); }
function csrf_campo_c() { return '<input type="hidden" name="csrf" value="' . csrf_c() . '">'; }
function csrf_ok_c() { return hash_equals($_SESSION['csrf_c'] ?? '', $_POST['csrf'] ?? ''); }
function voltar_seguro($v) { return in_array($v, ['checkout.php', 'loja.php', 'minha_conta.php', 'index.php'], true) ? $v : 'minha_conta.php'; }
function numero_pedido($p) { return 'LV-' . date('Y', strtotime($p['criado_em'])) . '-' . str_pad($p['id'], 6, '0', STR_PAD_LEFT); }
function fmt_cpf($s) { $s = preg_replace('/\D/', '', (string)$s); return strlen($s) == 11 ? substr($s, 0, 3) . '.' . substr($s, 3, 3) . '.' . substr($s, 6, 3) . '-' . substr($s, 9) : $s; }
function fmt_tel($s) { $s = preg_replace('/\D/', '', (string)$s); return strlen($s) == 11 ? '(' . substr($s, 0, 2) . ') ' . substr($s, 2, 5) . '-' . substr($s, 7) : (strlen($s) == 10 ? '(' . substr($s, 0, 2) . ') ' . substr($s, 2, 4) . '-' . substr($s, 6) : $s); }

/* Dados para preencher o checkout: perfil + endereço mais recente criado depois da conta existir */
function dados_checkout($pdo, $id) {
    $s = $pdo->prepare("SELECT nome, cpf, telefone, email, conta_desde FROM clientes WHERE id = ?"); $s->execute([$id]); $c = $s->fetch();
    if (!$c) return [];
    $r = ['nome' => $c['nome'], 'cpf' => fmt_cpf($c['cpf']), 'telefone' => fmt_tel($c['telefone']), 'email' => $c['email']];
    $s = $pdo->prepare("SELECT * FROM enderecos WHERE cliente_id = ? AND criado_em >= ? ORDER BY id DESC LIMIT 1"); $s->execute([$id, $c['conta_desde']]);
    if ($e = $s->fetch()) foreach (['cep', 'estado', 'cidade', 'bairro', 'rua', 'numero', 'complemento'] as $k) $r[$k] = $e[$k];
    return $r;
}
