<?php require_once __DIR__ . '/conta.php'; $cl = cliente_logado(); $busca_atual = trim($_GET['busca'] ?? ''); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h(($titulo ?? 'Tênis e calçados das melhores marcas') . ' | Leve Calçados') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/leve.css">
</head>
<body>
<div class="aviso">Use o cupom <b>LEVE10</b> e ganhe 10% de desconto na primeira compra</div>
<header class="topo"><div class="wrap bar">
  <a class="logo" href="index.php"><img src="assets/img/logo.png" alt="Leve Calçados"><span>Leve <b>Calçados</b></span></a>
  <form class="busca" action="loja.php" method="get" role="search">
    <input name="busca" value="<?= h($busca_atual) ?>" placeholder="Buscar tênis ou marca..." aria-label="Buscar produtos">
    <button aria-label="Buscar"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></button>
  </form>
  <nav><a href="index.php">Início</a><a href="loja.php">Loja</a><a href="loja.php?ordem=novos">Lançamentos</a><a href="index.php#marcas">Marcas</a></nav>
  <a class="conta-link" href="<?= $cl ? 'minha_conta.php' : 'entrar.php' ?>"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg><span><?= $cl ? h(explode(' ', $cl['nome'])[0]) : 'Entrar' ?></span></a>
  <button id="cbtn" class="cart-btn" aria-label="Abrir carrinho">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
    <span id="cbadge">0</span>
  </button>
</div></header>
<div id="ov"></div>
<aside id="drawer" aria-label="Carrinho">
  <div class="dh"><h3>Seu carrinho</h3><button id="cx" aria-label="Fechar">✕</button></div>
  <div id="citens"></div>
  <div class="df">
    <div class="row"><span>Itens</span><b id="cqtd">0</b></div>
    <div class="row total"><span>Subtotal</span><b id="csub">R$ 0,00</b></div>
    <a class="btn" id="cgo" href="checkout.php">Finalizar compra</a>
    <button class="btn ghost" id="cclr">Esvaziar carrinho</button>
  </div>
</aside>
<div id="toasts"></div>
<main>
