<?php
require_once 'includes/auth.php';
if (!(int)$pdo->query("SELECT COUNT(*) FROM administradores")->fetchColumn()) { header('Location: criar_admin.php'); exit; }
if (!empty($_SESSION['admin'])) { header('Location: index.php'); exit; }
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) $erro = 'Sessão expirada. Tente novamente.';
    else {
        $s = $pdo->prepare("SELECT * FROM administradores WHERE email = ?"); $s->execute([trim($_POST['email'] ?? '')]); $a = $s->fetch();
        if ($a && password_verify($_POST['senha'] ?? '', $a['senha_hash'])) {
            session_regenerate_id(true); $_SESSION['admin'] = ['id' => $a['id'], 'nome' => $a['nome']]; header('Location: index.php'); exit;
        }
        $erro = 'E-mail ou senha incorretos.'; usleep(400000);
    }
}
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Entrar | Leve Admin</title><link rel="stylesheet" href="css/admin.css"></head>
<body class="login"><form method="post" class="box">
  <img src="../assets/img/logo.png" alt="Leve Calçados"><h1>Painel administrativo</h1>
  <?php if ($erro): ?><p class="msg erro"><?= h($erro) ?></p><?php endif; ?>
  <?= csrf_campo() ?>
  <label>E-mail<input type="email" name="email" required autofocus value="<?= h($_POST['email'] ?? '') ?>"></label>
  <label>Senha<input type="password" name="senha" required></label>
  <button class="btn">Entrar</button><a class="mut" href="../index.php">← Voltar à loja</a>
</form></body></html>
