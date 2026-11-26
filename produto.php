<?php
require_once 'includes/funcoes.php';
$p = produto_por_slug($pdo, $_GET['slug'] ?? '');
if (!$p) { http_response_code(404); $titulo = 'Produto não encontrado'; require 'includes/topo.php'; echo '<div class="wrap sec"><p class="vazio">Produto não encontrado. <a href="loja.php">Voltar à loja</a></p></div>'; require 'includes/rodape.php'; exit; }
$titulo = $p['nome'];
$tams = tamanhos_produto($pdo, $p['id']);
$disp = array_sum(array_column($tams, 'estoque')) > 0;
$dados = ['id' => (int)$p['id'], 'nome' => $p['nome'], 'marca' => $p['marca'], 'preco' => (float)$p['preco'], 'foto' => foto($p)];
$rel = array_slice(array_filter(listar_produtos($pdo, ['marca' => $p['marca_slug']]), fn($x) => $x['id'] != $p['id']), 0, 4);
require_once 'includes/topo.php';
?>
<div class="wrap sec">
<p class="mig"><a href="index.php">Início</a> › <a href="loja.php?marca=<?= h($p['marca_slug']) ?>"><?= h($p['marca']) ?></a> › <?= h($p['nome']) ?></p>
<div class="pd" id="prod" data-p="<?= h(json_encode($dados)) ?>">
  <div class="zoom"><img src="<?= h(foto($p)) ?>" alt="<?= h($p['nome']) ?>"></div>
  <div class="pd-info">
    <span class="pill"><?= h($p['marca']) ?> · <?= h($p['categoria']) ?></span>
    <h1><?= h($p['nome']) ?></h1>
    <b class="preco big"><?= moeda($p['preco']) ?></b>
    <small>ou 3x de <?= moeda($p['preco'] / 3) ?> sem juros · PIX com pagamento imediato</small>
    <p class="desc"><?= h($p['descricao']) ?></p>
    <h4>Numeração <span id="tamlbl">(selecione)</span></h4>
    <div class="tams"><?php foreach ($tams as $t): ?>
      <button type="button" class="tam" data-n="<?= (int)$t['numero'] ?>" data-max="<?= (int)$t['estoque'] ?>" <?= $t['estoque'] < 1 ? 'disabled title="Esgotado"' : '' ?>><?= (int)$t['numero'] ?></button><?php endforeach; ?></div>
    <small>Números riscados estão esgotados.</small>
    <button class="btn lg" id="addp" <?= $disp ? '' : 'disabled' ?>><?= $disp ? 'Adicionar ao carrinho' : 'Produto indisponível' ?></button>
  </div>
</div>
<?php if ($rel): ?><div class="sec-h" style="margin-top:48px"><h2>Mais da <?= h($p['marca']) ?></h2></div><div class="grid"><?php foreach ($rel as $p) include 'includes/card.php'; ?></div><?php endif; ?>
</div>
<?php require_once 'includes/rodape.php'; ?>
