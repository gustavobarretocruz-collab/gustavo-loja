<?php
session_start();
require_once 'includes/funcoes.php';
$p = $_SESSION['pedido'] ?? null;
if (!$p) { header('Location: loja.php'); exit; }
$nomes = ['pix' => 'PIX', 'debito' => 'Cartão de débito', 'credito' => 'Cartão de crédito'];
$titulo = 'Pedido confirmado';
require_once 'includes/topo.php';
?>
<div class="wrap sec"><section class="ok-box">
  <div class="check">✓</div>
  <h1>Pedido confirmado!</h1>
  <p>Obrigado, <b><?= h(explode(' ', $p['nome'])[0]) ?></b>! Os detalhes foram registrados para <b><?= h($p['email']) ?></b>.</p>
  <div class="num">Pedido nº <b>LV-<?= date('Y') ?>-<?= str_pad($p['id'], 6, '0', STR_PAD_LEFT) ?></b></div>
  <ul class="resumo">
    <li><span>Total</span><b><?= moeda($p['total']) ?></b></li>
    <li><span>Pagamento</span><b><?= $nomes[$p['pagamento']] ?><?= $p['pagamento'] === 'credito' && $p['parcelas'] > 1 ? " em {$p['parcelas']}x" : '' ?></b></li>
    <li><span>Entrega em</span><b><?= h($p['endereco']) ?></b></li>
  </ul>
  <p class="nota">Pagamento em modo demonstrativo: nenhuma cobrança real foi feita.</p>
  <a class="btn" href="loja.php">Continuar comprando</a>
</section></div>
<script>try{localStorage.removeItem('leve_cart')}catch(e){}</script>
<?php require_once 'includes/rodape.php'; ?>
