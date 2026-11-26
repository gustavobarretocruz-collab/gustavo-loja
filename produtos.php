<?php
$pag = 'produtos'; $titulo = 'Produtos';
require_once 'includes/topo.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok() && isset($_POST['alternar'])) {
    $pdo->prepare("UPDATE produtos SET ativo = 1 - ativo WHERE id = ?")->execute([(int)$_POST['alternar']]);
    header('Location: produtos.php?' . http_build_query(['busca' => $_GET['busca'] ?? '', 'ok' => 2])); exit;
}
$b = trim($_GET['busca'] ?? '');
$s = $pdo->prepare("SELECT p.*, m.nome AS marca, COALESCE(SUM(t.estoque),0) AS estoque FROM produtos p JOIN marcas m ON m.id = p.marca_id
    LEFT JOIN produto_tamanhos t ON t.produto_id = p.id WHERE p.nome LIKE ? OR m.nome LIKE ? GROUP BY p.id, m.nome ORDER BY p.nome");
$s->execute(["%$b%", "%$b%"]); $ps = $s->fetchAll();
?>
<div class="topo-pg"><h1>Produtos</h1><a class="btn" href="produto_form.php">+ Novo produto</a></div>
<?php if (($_GET['ok'] ?? '') == 1): ?><p class="msg ok">Produto salvo com sucesso.</p><?php elseif (($_GET['ok'] ?? '') == 3): ?><p class="msg erro">Produto salvo, mas a foto não pôde ser gravada. Verifique se a pasta assets/produtos permite escrita.</p><?php elseif (($_GET['ok'] ?? '') == 2): ?><p class="msg ok">Visibilidade atualizada.</p><?php endif; ?>
<form class="busca" method="get"><input name="busca" placeholder="Buscar por nome ou marca" value="<?= h($b) ?>"><button class="btn ghost">Buscar</button></form>
<table class="lista"><tr><th></th><th>Produto</th><th>Marca</th><th>Preço</th><th>Estoque</th><th>Status</th><th></th></tr>
<?php foreach ($ps as $p): ?>
<tr class="<?= $p['ativo'] ? '' : 'off' ?>">
  <td><img class="mini" src="../<?= h(foto($p)) ?>" alt=""></td>
  <td><b><?= h($p['nome']) ?></b><br><small><?= h($p['slug']) ?></small></td><td><?= h($p['marca']) ?></td><td><?= moeda($p['preco']) ?></td>
  <td><?= (int)$p['estoque'] ?></td><td><span class="pill <?= $p['ativo'] ? 'ok' : 'er' ?>"><?= $p['ativo'] ? 'Ativo' : 'Oculto' ?></span></td>
  <td class="acoes"><a href="produto_form.php?id=<?= $p['id'] ?>">Editar</a>
    <form method="post"><?= csrf_campo() ?><button name="alternar" value="<?= $p['id'] ?>" class="link"><?= $p['ativo'] ? 'Ocultar' : 'Reativar' ?></button></form></td>
</tr><?php endforeach; ?>
</table>
<?php if (!$ps): ?><p class="mut">Nenhum produto encontrado.</p><?php endif; ?>
<p class="mut">Produtos com pedidos não podem ser excluídos (histórico); use "Ocultar" para tirá-los da loja.</p>
<?php require_once 'includes/rodape.php'; ?>
