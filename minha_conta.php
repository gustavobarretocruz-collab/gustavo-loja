<?php
require_once 'includes/conta.php';
$conta = cliente_logado();
if (!$conta) { header('Location: entrar.php?voltar=minha_conta.php'); exit; }
$id = (int)$conta['id']; $msg = ''; $e = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok_c()) $e[] = 'Sessão expirada. Tente novamente.';
    elseif (($_POST['acao'] ?? '') === 'dados') {
        $n = trim($_POST['nome'] ?? ''); $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? ''); $tel = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
        if (count(preg_split('/\s+/', $n)) < 2) $e[] = 'Informe o nome completo.';
        if (!cpf_valido($cpf)) $e[] = 'CPF inválido.';
        if (!preg_match('/^\d{10,11}$/', $tel)) $e[] = 'Telefone inválido.';
        if (!$e) { $pdo->prepare("UPDATE clientes SET nome=?, cpf=?, telefone=? WHERE id=?")->execute([$n, $cpf, $tel, $id]); $_SESSION['cliente']['nome'] = $n; $conta['nome'] = $n; $msg = 'Dados atualizados.'; }
    } elseif (($_POST['acao'] ?? '') === 'senha') {
        $s = $pdo->prepare("SELECT senha_hash FROM clientes WHERE id = ?"); $s->execute([$id]);
        if (!password_verify($_POST['atual'] ?? '', (string)$s->fetchColumn())) $e[] = 'A senha atual está incorreta.';
        if (strlen($_POST['nova'] ?? '') < 8) $e[] = 'A nova senha deve ter pelo menos 8 caracteres.';
        if (($_POST['nova'] ?? '') !== ($_POST['nova2'] ?? '')) $e[] = 'As senhas novas não conferem.';
        if (!$e) { $pdo->prepare("UPDATE clientes SET senha_hash=? WHERE id=?")->execute([password_hash($_POST['nova'], PASSWORD_DEFAULT), $id]); $msg = 'Senha alterada.'; }
    }
}
$d = dados_checkout($pdo, $id);
$s = $pdo->prepare("SELECT pe.* FROM pedidos pe JOIN clientes c ON c.id = pe.cliente_id WHERE pe.cliente_id = ? AND pe.criado_em >= c.conta_desde ORDER BY pe.id DESC");
$s->execute([$id]); $ped = $s->fetchAll(); $itens = [];
if ($ped) {
    $s = $pdo->prepare("SELECT i.*, pr.nome FROM itens_pedido i JOIN produtos pr ON pr.id = i.produto_id WHERE i.pedido_id IN (" . implode(',', array_fill(0, count($ped), '?')) . ")");
    $s->execute(array_column($ped, 'id')); foreach ($s->fetchAll() as $i) $itens[$i['pedido_id']][] = $i;
}
$pg = ['pix' => 'PIX', 'debito' => 'Débito', 'credito' => 'Crédito'];
$titulo = 'Minha conta';
require_once 'includes/topo.php';
?>
<div class="wrap sec">
<div class="sec-h"><h1 class="tit">Minha conta</h1><a href="sair.php" class="btn ghost">Sair</a></div>
<?php if ($msg): ?><p class="msg ok"><?= h($msg) ?></p><?php endif; ?>
<?php foreach ($e as $x): ?><p class="msg erro"><?= h($x) ?></p><?php endforeach; ?>
<div class="conta-grid">
  <div>
    <form method="post" class="painel-c"><h2>Meus dados</h2><?= csrf_campo_c() ?><input type="hidden" name="acao" value="dados">
      <label>Nome completo<input name="nome" required value="<?= h($d['nome'] ?? '') ?>"></label>
      <label>E-mail<input value="<?= h($conta['email']) ?>" readonly></label>
      <div class="fg"><label>CPF<input name="cpf" inputmode="numeric" value="<?= h($d['cpf'] ?? '') ?>"></label><label>Telefone<input name="telefone" inputmode="tel" value="<?= h($d['telefone'] ?? '') ?>"></label></div>
      <button class="btn">Salvar dados</button></form>
    <div class="painel-c"><h2>Endereço salvo</h2>
      <?php if (!empty($d['rua'])): ?><p><?= h($d['rua']) ?>, <?= h($d['numero']) ?> <?= h($d['complemento'] ?? '') ?><br><?= h($d['bairro']) ?> — <?= h($d['cidade']) ?>/<?= h($d['estado']) ?><br>CEP <?= h($d['cep']) ?></p>
      <p class="nota">É o endereço da sua última compra como conta; ele já vem preenchido no checkout.</p>
      <?php else: ?><p class="nota">Você ainda não fez compras com a conta. O endereço da sua primeira compra será salvo aqui.</p><?php endif; ?></div>
    <form method="post" class="painel-c"><h2>Alterar senha</h2><?= csrf_campo_c() ?><input type="hidden" name="acao" value="senha">
      <label>Senha atual<input type="password" name="atual" required></label><label>Nova senha<input type="password" name="nova" required></label><label>Repita a nova senha<input type="password" name="nova2" required></label>
      <button class="btn ghost">Alterar senha</button></form>
  </div>
  <div class="painel-c"><h2>Meus pedidos</h2>
    <?php if (!$ped): ?><p class="vazio">Você ainda não tem pedidos nesta conta. <a href="loja.php">Ver produtos</a></p><?php endif; ?>
    <?php foreach ($ped as $p): ?>
    <div class="ped"><div class="ped-h"><b><?= h(numero_pedido($p)) ?></b><span><?= date('d/m/Y', strtotime($p['criado_em'])) ?></span></div>
      <?php foreach ($itens[$p['id']] ?? [] as $i): ?><div class="ped-i"><span><?= (int)$i['quantidade'] ?>× <?= h($i['nome']) ?> (nº <?= (int)$i['tamanho'] ?>)</span><span><?= moeda($i['preco_unitario'] * $i['quantidade']) ?></span></div><?php endforeach; ?>
      <div class="ped-f"><span><?= $pg[$p['pagamento']] ?><?= $p['pagamento'] === 'credito' ? " {$p['parcelas']}x" : '' ?> · <?= h($p['status']) ?></span><b><?= moeda($p['total']) ?></b></div></div>
    <?php endforeach; ?>
  </div>
</div></div>
<?php require_once 'includes/rodape.php'; ?>
