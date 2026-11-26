<?php
$pag = 'home'; $titulo = 'Visão geral';
require_once 'includes/topo.php';
$n = fn($sql) => $pdo->query($sql)->fetchColumn();
$baixo = $pdo->query("SELECT p.nome, t.numero, t.estoque FROM produto_tamanhos t JOIN produtos p ON p.id = t.produto_id WHERE p.ativo = 1 AND t.estoque <= 2 ORDER BY t.estoque, p.nome LIMIT 10")->fetchAll();
$ult = $pdo->query("SELECT pe.*, c.nome AS cliente FROM pedidos pe JOIN clientes c ON c.id = pe.cliente_id ORDER BY pe.id DESC LIMIT 5")->fetchAll();
?>
<h1>Visão geral</h1>
<div class="cards">
  <div class="card"><span><?= $n("SELECT COUNT(*) FROM produtos WHERE ativo = 1") ?></span>Produtos ativos</div>
  <div class="card"><span><?= $n("SELECT COUNT(*) FROM pedidos") ?></span>Pedidos</div>
  <div class="card"><span><?= moeda($n("SELECT COALESCE(SUM(total),0) FROM pedidos")) ?></span>Faturamento</div>
  <div class="card"><span><?= $n("SELECT COUNT(*) FROM clientes") ?></span>Clientes</div>
</div>
<div class="duo">
  <section class="painel"><h2>Estoque baixo (2 ou menos)</h2>
    <?php if (!$baixo): ?><p class="mut">Nenhum item com estoque baixo.</p><?php else: ?>
    <table><tr><th>Produto</th><th>Nº</th><th>Estoque</th></tr>
    <?php foreach ($baixo as $b): ?><tr><td><?= h($b['nome']) ?></td><td><?= (int)$b['numero'] ?></td><td><span class="pill <?= $b['estoque'] < 1 ? 'er' : 'av' ?>"><?= (int)$b['estoque'] ?></span></td></tr><?php endforeach; ?></table><?php endif; ?>
  </section>
  <section class="painel"><h2>Últimos pedidos</h2>
    <?php if (!$ult): ?><p class="mut">Ainda não há pedidos.</p><?php else: ?>
    <table><tr><th>Pedido</th><th>Cliente</th><th>Total</th></tr>
    <?php foreach ($ult as $p): ?><tr><td><a href="pedidos.php?id=<?= $p['id'] ?>"><?= h(num_pedido($p)) ?></a></td><td><?= h($p['cliente']) ?></td><td><?= moeda($p['total']) ?></td></tr><?php endforeach; ?></table><?php endif; ?>
  </section>
</div>
<?php require_once 'includes/rodape.php'; ?>
