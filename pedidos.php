<?php
$pag = 'pedidos'; $titulo = 'Pedidos';
require_once 'includes/topo.php';
$status = ['pago (demonstração)', 'enviado', 'entregue']; $pg = ['pix' => 'PIX', 'debito' => 'Débito', 'credito' => 'Crédito'];
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok() && in_array($_POST['status'] ?? '', $status)) {
        $pdo->prepare("UPDATE pedidos SET status = ? WHERE id = ?")->execute([$_POST['status'], $id]); header("Location: pedidos.php?id=$id&ok=1"); exit;
    }
    $s = $pdo->prepare("SELECT pe.*, c.nome, c.email, c.cpf, c.telefone, en.cep, en.estado, en.cidade, en.bairro, en.rua, en.numero, en.complemento
        FROM pedidos pe JOIN clientes c ON c.id = pe.cliente_id JOIN enderecos en ON en.id = pe.endereco_id WHERE pe.id = ?");
    $s->execute([$id]); $p = $s->fetch();
    if (!$p) { echo '<p class="msg erro">Pedido não encontrado.</p><a href="pedidos.php">← Voltar</a>'; require 'includes/rodape.php'; exit; }
    $s = $pdo->prepare("SELECT i.*, pr.nome FROM itens_pedido i JOIN produtos pr ON pr.id = i.produto_id WHERE i.pedido_id = ?"); $s->execute([$id]); $itens = $s->fetchAll();
?>
<div class="topo-pg"><h1>Pedido <?= h(num_pedido($p)) ?></h1><a class="btn ghost" href="pedidos.php">← Voltar</a></div>
<?php if (isset($_GET['ok'])): ?><p class="msg ok">Status atualizado.</p><?php endif; ?>
<div class="duo">
  <section class="painel"><h2>Cliente</h2><p><b><?= h($p['nome']) ?></b><br><?= h($p['email']) ?><br><?= h($p['telefone']) ?><br>CPF <?= h($p['cpf']) ?></p>
    <h2>Entrega</h2><p><?= h($p['rua']) ?>, <?= h($p['numero']) ?> <?= h($p['complemento']) ?><br><?= h($p['bairro']) ?> — <?= h($p['cidade']) ?>/<?= h($p['estado']) ?><br>CEP <?= h($p['cep']) ?></p></section>
  <section class="painel"><h2>Pagamento</h2><p><?= $pg[$p['pagamento']] ?><?= $p['pagamento'] === 'credito' ? " em {$p['parcelas']}x" : '' ?><br>Subtotal <?= moeda($p['subtotal']) ?>
    <?php if ($p['desconto'] > 0): ?><br>Desconto (<?= h($p['cupom']) ?>) − <?= moeda($p['desconto']) ?><?php endif; ?><br><b>Total <?= moeda($p['total']) ?></b></p>
    <form method="post" class="inl"><?= csrf_campo() ?><label>Status<select name="status"><?php foreach ($status as $st): ?><option <?= $p['status'] === $st ? 'selected' : '' ?>><?= h($st) ?></option><?php endforeach; ?></select></label><button class="btn">Atualizar</button></form></section>
</div>
<section class="painel"><h2>Itens</h2><table><tr><th>Produto</th><th>Nº</th><th>Qtd</th><th>Unitário</th><th>Total</th></tr>
<?php foreach ($itens as $i): ?><tr><td><?= h($i['nome']) ?></td><td><?= (int)$i['tamanho'] ?></td><td><?= (int)$i['quantidade'] ?></td><td><?= moeda($i['preco_unitario']) ?></td><td><?= moeda($i['preco_unitario'] * $i['quantidade']) ?></td></tr><?php endforeach; ?></table></section>
<?php } else {
    $ps = $pdo->query("SELECT pe.*, c.nome AS cliente FROM pedidos pe JOIN clientes c ON c.id = pe.cliente_id ORDER BY pe.id DESC LIMIT 100")->fetchAll(); ?>
<h1>Pedidos</h1>
<?php if (!$ps): ?><p class="mut">Ainda não há pedidos.</p><?php else: ?>
<table class="lista"><tr><th>Pedido</th><th>Data</th><th>Cliente</th><th>Pagamento</th><th>Total</th><th>Status</th></tr>
<?php foreach ($ps as $p): ?><tr><td><a href="pedidos.php?id=<?= $p['id'] ?>"><?= h(num_pedido($p)) ?></a></td><td><?= date('d/m/Y H:i', strtotime($p['criado_em'])) ?></td><td><?= h($p['cliente']) ?></td><td><?= $pg[$p['pagamento']] ?></td><td><?= moeda($p['total']) ?></td><td><span class="pill av"><?= h($p['status']) ?></span></td></tr><?php endforeach; ?></table><?php endif; ?>
<?php } require_once 'includes/rodape.php'; ?>
