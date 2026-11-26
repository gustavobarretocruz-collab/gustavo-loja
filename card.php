<a class="card-p" href="produto.php?slug=<?= h($p['slug']) ?>">
  <div class="foto"><img loading="lazy" src="<?= h(foto($p)) ?>" alt="<?= h($p['nome']) ?>">
    <?php if ($p['lancamento']): ?><span class="tag">Lançamento</span><?php endif; ?></div>
  <div class="info">
    <small><?= h($p['marca']) ?> · <?= h($p['categoria']) ?></small>
    <h3><?= h($p['nome']) ?></h3>
    <b class="preco"><?= moeda($p['preco']) ?></b>
    <small>ou 3x de <?= moeda($p['preco'] / 3) ?> sem juros</small>
    <span class="btn sm">Ver detalhes</span>
  </div>
</a>
