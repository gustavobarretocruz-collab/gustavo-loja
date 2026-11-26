<?php
require_once 'includes/conta.php';
if (cliente_logado()) { header('Location: minha_conta.php'); exit; }
$volta = voltar_seguro($_GET['voltar'] ?? $_POST['voltar'] ?? ''); $erro = '';
if (!empty($_SESSION['bloq']) && time() >= $_SESSION['bloq']) { $_SESSION['falhas'] = 0; $_SESSION['bloq'] = 0; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok_c()) $erro = 'Sessão expirada. Tente novamente.';
    elseif (!empty($_SESSION['bloq'])) $erro = 'Muitas tentativas. Aguarde 1 minuto e tente de novo.';
    else {
        $s = $pdo->prepare("SELECT id, nome, email, senha_hash FROM clientes WHERE email = ? AND senha_hash IS NOT NULL"); $s->execute([trim($_POST['email'] ?? '')]); $c = $s->fetch();
        if ($c && password_verify($_POST['senha'] ?? '', $c['senha_hash'])) {
            session_regenerate_id(true); $_SESSION['cliente'] = ['id' => $c['id'], 'nome' => $c['nome'], 'email' => $c['email']]; $_SESSION['falhas'] = 0;
            header('Location: ' . $volta); exit;
        }
        $_SESSION['falhas'] = ($_SESSION['falhas'] ?? 0) + 1; if ($_SESSION['falhas'] >= 5) $_SESSION['bloq'] = time() + 60;
        $erro = 'E-mail ou senha incorretos.'; usleep(400000);
    }
}
$titulo = 'Entrar';
require_once 'includes/topo.php';
?>
<div class="wrap sec"><form method="post" class="auth-box">
  <h1>Entrar na minha conta</h1><p class="nota">Ter conta é opcional: você pode comprar como visitante.</p>
  <?php if ($erro): ?><p class="msg erro"><?= h($erro) ?></p><?php endif; ?>
  <?= csrf_campo_c() ?><input type="hidden" name="voltar" value="<?= h($volta) ?>">
  <label>E-mail<input type="email" name="email" required autofocus value="<?= h($_POST['email'] ?? '') ?>"></label>
  <label>Senha<input type="password" name="senha" required></label>
  <button class="btn lg">Entrar</button>
  <p class="nota">Ainda não tem conta? <a href="cadastro.php?voltar=<?= h($volta) ?>">Criar conta</a> · <a href="loja.php">Continuar sem conta</a></p>
</form></div>
<?php require_once 'includes/rodape.php'; ?>
