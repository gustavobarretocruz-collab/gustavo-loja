<?php
require_once 'includes/conta.php';
if (cliente_logado()) { header('Location: minha_conta.php'); exit; }
$volta = voltar_seguro($_GET['voltar'] ?? $_POST['voltar'] ?? ''); $e = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $n = trim($_POST['nome'] ?? ''); $m = trim($_POST['email'] ?? ''); $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? ''); $tel = preg_replace('/\D/', '', $_POST['telefone'] ?? ''); $sn = $_POST['senha'] ?? '';
    if (!csrf_ok_c()) $e[] = 'Sessão expirada. Tente novamente.';
    if (count(preg_split('/\s+/', $n)) < 2) $e[] = 'Informe o nome completo.';
    if (!filter_var($m, FILTER_VALIDATE_EMAIL)) $e[] = 'E-mail inválido.';
    if (!cpf_valido($cpf)) $e[] = 'CPF inválido.';
    if (!preg_match('/^\d{10,11}$/', $tel)) $e[] = 'Telefone inválido.';
    if (strlen($sn) < 8) $e[] = 'A senha deve ter pelo menos 8 caracteres.';
    if ($sn !== ($_POST['senha2'] ?? '')) $e[] = 'As senhas não conferem.';
    if (!$e) {
        $s = $pdo->prepare("SELECT id, senha_hash FROM clientes WHERE email = ?"); $s->execute([$m]); $x = $s->fetch();
        if ($x && $x['senha_hash']) $e[] = 'Este e-mail já tem conta. <a href="entrar.php">Entrar</a>';
        else {
            $hash = password_hash($sn, PASSWORD_DEFAULT);
            if ($x) { $pdo->prepare("UPDATE clientes SET nome=?, cpf=?, telefone=?, senha_hash=?, conta_desde=NOW() WHERE id=?")->execute([$n, $cpf, $tel, $hash, $x['id']]); $id = $x['id']; }
            else { $pdo->prepare("INSERT INTO clientes (nome,cpf,telefone,email,senha_hash,conta_desde) VALUES (?,?,?,?,?,NOW())")->execute([$n, $cpf, $tel, $m, $hash]); $id = $pdo->lastInsertId(); }
            session_regenerate_id(true); $_SESSION['cliente'] = ['id' => $id, 'nome' => $n, 'email' => $m]; header('Location: ' . $volta); exit;
        }
    }
}
$titulo = 'Criar conta';
require_once 'includes/topo.php';
?>
<div class="wrap sec"><form method="post" class="auth-box" id="cad">
  <h1>Criar conta</h1><p class="nota">Opcional: guarda seus dados e o histórico das suas compras.</p>
  <?php foreach ($e as $x): ?><p class="msg erro"><?= strpos($x, '<a') !== false ? $x : h($x) ?></p><?php endforeach; ?>
  <?= csrf_campo_c() ?><input type="hidden" name="voltar" value="<?= h($volta) ?>">
  <label>Nome completo<input name="nome" required value="<?= h($_POST['nome'] ?? '') ?>"></label>
  <label>E-mail<input type="email" name="email" required value="<?= h($_POST['email'] ?? '') ?>"></label>
  <div class="fg"><label>CPF<input name="cpf" required inputmode="numeric" placeholder="000.000.000-00" value="<?= h($_POST['cpf'] ?? '') ?>"></label>
  <label>Telefone<input name="telefone" required inputmode="tel" placeholder="(00) 00000-0000" value="<?= h($_POST['telefone'] ?? '') ?>"></label></div>
  <label>Senha (mínimo 8 caracteres)<input type="password" name="senha" required></label>
  <label>Repita a senha<input type="password" name="senha2" required></label>
  <button class="btn lg">Criar conta</button>
  <p class="nota">Já tem conta? <a href="entrar.php?voltar=<?= h($volta) ?>">Entrar</a></p>
</form></div>
<?php require_once 'includes/rodape.php'; ?>
