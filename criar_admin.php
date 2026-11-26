<?php
require_once 'includes/auth.php';
if ((int)$pdo->query("SELECT COUNT(*) FROM administradores")->fetchColumn()) { header('Location: login.php'); exit; } // só funciona enquanto não há administrador
$e = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $n = trim($_POST['nome'] ?? ''); $m = trim($_POST['email'] ?? ''); $s = $_POST['senha'] ?? '';
    if (!csrf_ok()) $e[] = 'Sessão expirada. Tente novamente.';
    if (strlen($n) < 3) $e[] = 'Informe o nome.';
    if (!filter_var($m, FILTER_VALIDATE_EMAIL)) $e[] = 'E-mail inválido.';
    if (strlen($s) < 8) $e[] = 'A senha deve ter pelo menos 8 caracteres.';
    if ($s !== ($_POST['senha2'] ?? '')) $e[] = 'As senhas não conferem.';
    if (!$e) { $pdo->prepare("INSERT INTO administradores (nome,email,senha_hash) VALUES (?,?,?)")->execute([$n, $m, password_hash($s, PASSWORD_DEFAULT)]); header('Location: login.php'); exit; }
}
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Criar administrador | Leve Admin</title><link rel="stylesheet" href="css/admin.css"></head>
<body class="login"><form method="post" class="box">
  <img src="../assets/img/logo.png" alt="Leve Calçados"><h1>Criar o primeiro administrador</h1>
  <?php foreach ($e as $x): ?><p class="msg erro"><?= h($x) ?></p><?php endforeach; ?>
  <?= csrf_campo() ?>
  <label>Nome<input name="nome" required value="<?= h($_POST['nome'] ?? '') ?>"></label>
  <label>E-mail<input type="email" name="email" required value="<?= h($_POST['email'] ?? '') ?>"></label>
  <label>Senha (mínimo 8 caracteres)<input type="password" name="senha" required></label>
  <label>Repita a senha<input type="password" name="senha2" required></label>
  <button class="btn">Criar e entrar</button>
</form></body></html>
