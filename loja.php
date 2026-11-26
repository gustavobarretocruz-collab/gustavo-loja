<?php
$titulo = 'Produtos';
require_once 'includes/funcoes.php';
$f = ['busca' => trim($_GET['busca'] ?? ''), 'marca' => $_GET['marca'] ?? '', 'tam' => (int)($_GET['tam'] ?? 0), 'ordem' => $_GET['ordem'] ?? '', 'faixa' => $_GET['faixa'] ?? ''];
if (preg_match('/^(\d*)-(\d*)$/', $f['faixa'], $m)) { $f['min'] = $m[1]; $f['max'] = $m[2]; }
$ps = listar_produtos($pdo, $f);
$faixas = ['0-300' => 'Até R$ 300', '300-600' => 'R$ 300 a R$ 600', '600-1000' => 'R$ 600 a R$ 1.000', '1000-' => 'Acima de R$ 1.000'];
$filtrado = $f['busca'] || $f['marca'] || $f['tam'] || $f['faixa'];
require_once 'includes/topo.php';
?>
<div class="wrap sec"><h1 class="tit">Todos os produtos</h1>
<form class="filtros" method="get" action="loja.php">
  <input name="busca" placeholder="Nome do produto" value="<?= h($f['busca']) ?>">
  <select name="marca" onchange="this.form.submit()"><option value="">Todas as marcas</option>
    <?php foreach (listar_marcas($pdo) as $mk): ?><option value="<?= h($mk['slug']) ?>" <?= $f['marca'] === $mk['slug'] ? 'selected' : '' ?>><?= h($mk['nome']) ?></option><?php endforeach; ?></select>
  <select name="faixa" onchange="this.form.submit()"><option value="">Qualquer preço</option>
    <?php foreach ($faixas as $k => $v): ?><option value="<?= $k ?>" <?= $f['faixa'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
  <select name="tam" onchange="this.form.submit()"><option value="0">Numeração</option>
    <?php for ($n = 38; $n <= 44; $n++): ?><option value="<?= $n ?>" <?= $f['tam'] == $n ? 'selected' : '' ?>>Nº <?= $n ?></option><?php endfor; ?></select>
  <select name="ordem" onchange="this.form.submit()">
    <?php foreach (['' => 'Ordenar: A–Z', 'menor' => 'Menor preço', 'maior' => 'Maior preço', 'novos' => 'Lançamentos'] as $k => $v): ?><option value="<?= $k ?>" <?= $f['ordem'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
  <button class="btn">Buscar</button>
  <?php if ($filtrado || $f['ordem']): ?><a class="btn ghost" href="loja.php">Limpar</a><?php endif; ?>
</form>
<p class="cont"><?= count($ps) ?> produto(s) encontrado(s)</p>
<?php if (!$ps): ?><p class="vazio">Nenhum produto encontrado com esses filtros.</p><?php endif; ?>
<div class="grid"><?php foreach ($ps as $p) include 'includes/card.php'; ?></div></div>
<?php require_once 'includes/rodape.php'; ?>
