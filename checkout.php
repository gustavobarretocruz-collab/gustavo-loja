<?php
session_start();
require_once 'includes/conta.php';
if (isset($_GET['cupom'])) { // consulta de cupom (AJAX)
    header('Content-Type: application/json');
    [$d, $m] = cupom_valor($_GET['cupom'], (float)($_GET['sub'] ?? 0));
    echo json_encode(['ok' => $d > 0, 'desc' => $d, 'msg' => $m ?: 'Digite um cupom.']); exit;
}
$conta = cliente_logado(); $erros = []; $f = $_POST;
if ($conta) { if ($_SERVER['REQUEST_METHOD'] !== 'POST') $f = dados_checkout($pdo, $conta['id']); else $f['email'] = $conta['email']; }
$f['conta_id'] = $conta['id'] ?? 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $erros = validar_pedido($f);
    if (!$erros) {
        try {
            [$id, $total, $end] = criar_pedido($pdo, $f, json_decode($f['itens'] ?? '[]', true) ?: []);
            $_SESSION['pedido'] = ['id' => $id, 'total' => $total, 'endereco' => $end, 'nome' => $f['nome'], 'email' => $f['email'],
                                   'pagamento' => $f['pagamento'], 'parcelas' => (int)($f['parcelas'] ?? 1)];
            header('Location: pedido.php'); exit;
        } catch (Exception $e) { $erros[] = $e->getMessage(); }
    }
}
$v = fn($k) => h($f[$k] ?? '');
$ufs = explode(' ', 'AC AL AP AM BA CE DF ES GO MA MT MS MG PA PB PR PE PI RJ RN RS RO RR SC SP SE TO');
$titulo = 'Finalizar compra';
require_once 'includes/topo.php';
?>
<div class="wrap sec"><h1 class="tit">Finalizar compra</h1>
<?php if ($conta): ?><p class="aviso-conta">Comprando como <b><?= h($conta['nome']) ?></b> · <a href="minha_conta.php">Minha conta</a></p>
<?php else: ?><p class="aviso-conta">Já tem conta? <a href="entrar.php?voltar=checkout.php">Entre</a> para usar seus dados salvos, ou continue como visitante.</p><?php endif; ?>
<?php foreach ($erros as $e): ?><p class="msg erro"><?= h($e) ?></p><?php endforeach; ?>
<form id="checkout" method="post" class="co" novalidate>
  <input type="hidden" name="itens" id="itens"><input type="hidden" name="cupom" id="cupomv" value="<?= $v('cupom') ?>">
  <div class="col">
    <fieldset><legend>1. Seus dados</legend><div class="fg">
      <label class="w2">Nome completo<input name="nome" value="<?= $v('nome') ?>" autocomplete="name"></label>
      <label>CPF<input name="cpf" value="<?= $v('cpf') ?>" inputmode="numeric" placeholder="000.000.000-00"></label>
      <label>Telefone<input name="telefone" value="<?= $v('telefone') ?>" inputmode="tel" placeholder="(00) 00000-0000"></label>
      <label class="w2">E-mail<input name="email" type="email" value="<?= $v('email') ?>" autocomplete="email" <?= $conta ? 'readonly' : '' ?>></label>
    </div></fieldset>
    <fieldset><legend>2. Endereço de entrega</legend><div class="fg">
      <label>CEP<input name="cep" value="<?= $v('cep') ?>" inputmode="numeric" placeholder="00000-000"></label>
      <label>Estado<select name="estado"><option value="">Selecione</option>
        <?php foreach ($ufs as $u): ?><option <?= ($f['estado'] ?? '') === $u ? 'selected' : '' ?>><?= $u ?></option><?php endforeach; ?></select></label>
      <label>Cidade<input name="cidade" value="<?= $v('cidade') ?>"></label>
      <label>Bairro<input name="bairro" value="<?= $v('bairro') ?>"></label>
      <label class="w2">Rua<input name="rua" value="<?= $v('rua') ?>"></label>
      <label>Número<input name="numero" value="<?= $v('numero') ?>"></label>
      <label>Complemento (opcional)<input name="complemento" value="<?= $v('complemento') ?>"></label>
    </div></fieldset>
    <fieldset><legend>3. Pagamento <small>(demonstração, sem cobrança real)</small></legend>
      <div class="pays">
        <?php foreach (['pix' => ['💠', 'PIX', 'Aprovação imediata'], 'debito' => ['💳', 'Débito', 'Cartão de débito'], 'credito' => ['🧾', 'Crédito', 'Até 12x sem juros']] as $k => [$ic, $t, $s]): ?>
        <label class="pay"><input type="radio" name="pagamento" value="<?= $k ?>" <?= ($f['pagamento'] ?? 'pix') === $k ? 'checked' : '' ?>><span class="ic"><?= $ic ?></span><b><?= $t ?></b><small><?= $s ?></small></label>
        <?php endforeach; ?>
      </div>
      <div id="p-pix" class="painel"><div id="qr"></div><div><p>Escaneie o QR Code no app do seu banco ou use o código <b>copia e cola</b>:</p>
        <code id="pixcode">00020126580014br.gov.bcb.pix0136leve-calcados-demonstracao5204000053039865802BR5913LEVE CALCADOS6009GUARAPUAVA6304ABCD</code>
        <button type="button" class="btn ghost" id="pixcopy">Copiar código</button><small class="nota">QR ilustrativo. Ao confirmar, o pedido é registrado como pago na demonstração.</small></div></div>
      <div id="p-cartao" class="painel fg" hidden>
        <label class="w2">Nome no cartão<input name="titular" value="<?= $v('titular') ?>" autocomplete="cc-name"></label>
        <label class="w2">Número do cartão<input name="cartao" inputmode="numeric" placeholder="0000 0000 0000 0000" autocomplete="cc-number"></label>
        <label>Validade<input name="validade" placeholder="MM/AA" inputmode="numeric" autocomplete="cc-exp"></label>
        <label>CVV<input name="cvv" placeholder="123" inputmode="numeric" maxlength="4" autocomplete="cc-csc"></label>
        <label class="w2" id="lparc">Parcelas<select name="parcelas" id="parcelas"></select></label>
        <small class="nota w2">Os dados do cartão são validados, mas nunca armazenados.</small>
      </div>
    </fieldset>
  </div>
  <aside class="col resumo-co">
    <h3>Resumo do pedido</h3>
    <div id="rlist"></div>
    <div class="cupom"><input id="cupom" placeholder="Cupom de desconto" value="<?= $v('cupom') ?>"><button type="button" class="btn ghost" id="cupomok">Aplicar</button></div>
    <div class="row"><span>Itens</span><b id="rq">0</b></div>
    <div class="row"><span>Subtotal</span><b id="rsub">R$ 0,00</b></div>
    <div class="row" id="rdescl" hidden><span>Desconto</span><b id="rdesc" class="verde"></b></div>
    <div class="row total"><span>Total</span><b id="rtot">R$ 0,00</b></div>
    <button class="btn lg" id="confirmar">Confirmar pedido</button>
    <a class="link" href="loja.php">← Continuar comprando</a>
  </aside>
</form></div>
<?php require_once 'includes/rodape.php'; ?>
