<?php
require_once 'includes/auth.php'; exigir_login();
const NUMS = [36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46];
function slugify($t) {
    $t = strtr(mb_strtolower($t, 'UTF-8'), ['á'=>'a','à'=>'a','â'=>'a','ã'=>'a','é'=>'e','ê'=>'e','í'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ç'=>'c']);
    return trim(preg_replace('/[^a-z0-9]+/', '-', $t), '-');
}
function validar_foto($f) { // retorna [erro, extensão]
    if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) return ['', ''];
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) return ['Falha no envio da foto.', ''];
    if ($f['size'] > 5 * 1024 * 1024) return ['A foto deve ter no máximo 5 MB.', ''];
    $i = @getimagesize($f['tmp_name']); $m = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    return $i && isset($m[$i[2]]) ? ['', $m[$i[2]]] : ['A foto deve ser JPG, PNG ou WEBP.', ''];
}
function salvar_foto($slug, $f, $ext) {
    foreach (['jpg', 'jpeg', 'png', 'webp'] as $e) { $x = __DIR__ . "/../assets/produtos/$slug.$e"; if (is_file($x)) @unlink($x); }
    return move_uploaded_file($f['tmp_name'], __DIR__ . "/../assets/produtos/$slug.$ext");
}

$id = (int)($_GET['id'] ?? 0); $e = [];
$p = ['nome' => '', 'slug' => '', 'marca_id' => 0, 'categoria_id' => 0, 'descricao' => '', 'preco' => '', 'cor' => 'branco', 'destaque' => 0, 'lancamento' => 0, 'ativo' => 1];
$est = [];
if ($id) {
    $s = $pdo->prepare("SELECT * FROM produtos WHERE id = ?"); $s->execute([$id]); $p = $s->fetch();
    if (!$p) { header('Location: produtos.php'); exit; }
    $s = $pdo->prepare("SELECT numero, estoque FROM produto_tamanhos WHERE produto_id = ?"); $s->execute([$id]);
    foreach ($s->fetchAll() as $r) $est[$r['numero']] = $r['estoque'];
}
$marcas = listar_marcas($pdo); $cats = $pdo->query("SELECT * FROM categorias ORDER BY nome")->fetchAll();
$cores = ['branco', 'preto', 'azul', 'cinza', 'verde', 'vermelho', 'laranja'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) $e[] = 'Sessão expirada. Tente novamente.';
    $d = ['nome' => trim($_POST['nome'] ?? ''), 'marca_id' => (int)($_POST['marca_id'] ?? 0), 'categoria_id' => (int)($_POST['categoria_id'] ?? 0),
          'descricao' => trim($_POST['descricao'] ?? ''), 'preco' => (float)str_replace(',', '.', $_POST['preco'] ?? ''), 'cor' => $_POST['cor'] ?? '',
          'destaque' => isset($_POST['destaque']) ? 1 : 0, 'lancamento' => isset($_POST['lancamento']) ? 1 : 0, 'ativo' => isset($_POST['ativo']) ? 1 : 0];
    $p = array_merge($p, $d, ['preco' => $_POST['preco'] ?? '']);
    if (strlen($d['nome']) < 3) $e[] = 'Informe o nome do produto.';
    if (!in_array($d['marca_id'], array_column($marcas, 'id'))) $e[] = 'Escolha a marca.';
    if (!in_array($d['categoria_id'], array_column($cats, 'id'))) $e[] = 'Escolha a categoria.';
    if ($d['preco'] <= 0 || $d['preco'] > 99999) $e[] = 'Informe um preço válido.';
    if (!in_array($d['cor'], $cores)) $e[] = 'Escolha a cor.';
    $est = [];
    foreach (NUMS as $n) { $v = trim($_POST['tam'][$n] ?? ''); if ($v === '') continue; if (!ctype_digit($v) || (int)$v > 9999) $e[] = "Estoque inválido no nº $n."; else $est[$n] = (int)$v; }
    if (!$est) $e[] = 'Informe o estoque de pelo menos uma numeração.';
    [$errFoto, $ext] = validar_foto($_FILES['foto'] ?? null); if ($errFoto) $e[] = $errFoto;
    if (!$e) {
        try {
            $pdo->beginTransaction();
            if ($id) {
                $pdo->prepare("UPDATE produtos SET nome=?, marca_id=?, categoria_id=?, descricao=?, preco=?, cor=?, destaque=?, lancamento=?, ativo=? WHERE id=?")
                    ->execute([...array_values($d), $id]);
                $slug = $p['slug'];
            } else {
                $slug = $base = slugify($d['nome']) ?: 'produto'; $k = 2;
                $c = $pdo->prepare("SELECT 1 FROM produtos WHERE slug = ?");
                while ($c->execute([$slug]) && $c->fetchColumn()) $slug = $base . '-' . $k++;
                $pdo->prepare("INSERT INTO produtos (nome, marca_id, categoria_id, descricao, preco, cor, destaque, lancamento, ativo, slug) VALUES (?,?,?,?,?,?,?,?,?,?)")
                    ->execute([...array_values($d), $slug]);
                $id = (int)$pdo->lastInsertId();
            }
            foreach ($est as $n => $q) $pdo->prepare("INSERT INTO produto_tamanhos (produto_id, numero, estoque) VALUES (?,?,?) ON DUPLICATE KEY UPDATE estoque = VALUES(estoque)")->execute([$id, $n, $q]);
            $in = implode(',', array_fill(0, count($est), '?'));
            $pdo->prepare("DELETE FROM produto_tamanhos WHERE produto_id = ? AND numero NOT IN ($in)")->execute([$id, ...array_keys($est)]);
            $pdo->commit();
            if ($ext && !salvar_foto($slug, $_FILES['foto'], $ext)) { header('Location: produtos.php?ok=3'); exit; }
            header('Location: produtos.php?ok=1'); exit;
        } catch (Exception $ex) { if ($pdo->inTransaction()) $pdo->rollBack(); $e[] = 'Erro ao salvar: ' . $ex->getMessage(); }
    }
}
$pag = 'produtos'; $titulo = $id ? 'Editar produto' : 'Novo produto';
require_once 'includes/topo.php';
?>
<div class="topo-pg"><h1><?= $titulo ?></h1><a class="btn ghost" href="produtos.php">← Voltar</a></div>
<?php foreach ($e as $x): ?><p class="msg erro"><?= h($x) ?></p><?php endforeach; ?>
<form method="post" enctype="multipart/form-data" class="form">
  <?= csrf_campo() ?>
  <section class="painel fg">
    <label class="w2">Nome do produto<input name="nome" required value="<?= h($p['nome']) ?>"></label>
    <label>Marca<select name="marca_id"><option value="">Selecione</option><?php foreach ($marcas as $m): ?><option value="<?= $m['id'] ?>" <?= $p['marca_id'] == $m['id'] ? 'selected' : '' ?>><?= h($m['nome']) ?></option><?php endforeach; ?></select></label>
    <label>Categoria<select name="categoria_id"><option value="">Selecione</option><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $p['categoria_id'] == $c['id'] ? 'selected' : '' ?>><?= h($c['nome']) ?></option><?php endforeach; ?></select></label>
    <label>Preço (R$)<input name="preco" inputmode="decimal" placeholder="599,90" value="<?= h($p['preco']) ?>"></label>
    <label>Cor principal<select name="cor"><?php foreach ($cores as $c): ?><option <?= $p['cor'] === $c ? 'selected' : '' ?>><?= $c ?></option><?php endforeach; ?></select></label>
    <label class="w2">Descrição<textarea name="descricao" rows="3"><?= h($p['descricao']) ?></textarea></label>
    <div class="w2 checks">
      <label><input type="checkbox" name="destaque" <?= $p['destaque'] ? 'checked' : '' ?>> Em destaque</label>
      <label><input type="checkbox" name="lancamento" <?= $p['lancamento'] ? 'checked' : '' ?>> Lançamento</label>
      <label><input type="checkbox" name="ativo" <?= $p['ativo'] ? 'checked' : '' ?>> Visível na loja</label>
    </div>
  </section>
  <section class="painel"><h2>Estoque por numeração</h2><p class="mut">Deixe em branco os números que você não vende.</p>
    <div class="tams"><?php foreach (NUMS as $n): ?><label>Nº <?= $n ?><input name="tam[<?= $n ?>]" inputmode="numeric" value="<?= isset($est[$n]) ? (int)$est[$n] : '' ?>"></label><?php endforeach; ?></div>
  </section>
  <section class="painel"><h2>Foto do produto</h2>
    <?php if ($id): ?><img class="prev" src="../<?= h(foto($p)) ?>" alt="Foto atual"><?php endif; ?>
    <label>Enviar nova foto (JPG, PNG ou WEBP, até 5 MB, de preferência quadrada)<input type="file" name="foto" accept="image/jpeg,image/png,image/webp"></label>
    <p class="mut">A foto é salva automaticamente com o nome do produto<?= $id ? ' (' . h($p['slug']) . ')' : '' ?>, substituindo a anterior.</p>
  </section>
  <button class="btn lg">Salvar produto</button>
</form>
<?php require_once 'includes/rodape.php'; ?>
