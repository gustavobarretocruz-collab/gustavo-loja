<?php
require_once __DIR__ . '/../config/conexao.php';

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function moeda($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }

/* Foto real: assets/produtos/<slug>.jpg|png|webp. Sem foto, usa a ilustração provisória. */
function foto($p) {
    foreach (['jpg', 'jpeg', 'png', 'webp'] as $e) {
        $f = __DIR__ . "/../assets/produtos/{$p['slug']}.$e";
        if (is_file($f)) return "assets/produtos/{$p['slug']}.$e?v=" . filemtime($f);
    }
    return 'imagem.php?c=' . urlencode($p['cor'] ?? '');
}

function listar_marcas($pdo) { return $pdo->query("SELECT * FROM marcas ORDER BY nome")->fetchAll(); }

function listar_produtos($pdo, $f = [], $limite = 0) {
    $w = ['p.ativo = 1']; $a = [];
    if (!empty($f['busca'])) { $w[] = '(p.nome LIKE ? OR m.nome LIKE ?)'; $a[] = "%{$f['busca']}%"; $a[] = "%{$f['busca']}%"; }
    if (!empty($f['marca'])) { $w[] = 'm.slug = ?'; $a[] = $f['marca']; }
    if (($f['min'] ?? '') !== '') { $w[] = 'p.preco >= ?'; $a[] = (float)$f['min']; }
    if (($f['max'] ?? '') !== '') { $w[] = 'p.preco <= ?'; $a[] = (float)$f['max']; }
    if (!empty($f['tam'])) { $w[] = 'EXISTS (SELECT 1 FROM produto_tamanhos t WHERE t.produto_id = p.id AND t.numero = ? AND t.estoque > 0)'; $a[] = (int)$f['tam']; }
    if (!empty($f['destaque'])) $w[] = 'p.destaque = 1';
    if (!empty($f['lancamento'])) $w[] = 'p.lancamento = 1';
    $o = ['menor' => 'p.preco ASC', 'maior' => 'p.preco DESC', 'novos' => 'p.lancamento DESC, p.id DESC'][$f['ordem'] ?? ''] ?? 'p.nome ASC';
    $sql = "SELECT p.*, m.nome AS marca, m.slug AS marca_slug, c.nome AS categoria FROM produtos p
            JOIN marcas m ON m.id = p.marca_id JOIN categorias c ON c.id = p.categoria_id
            WHERE " . implode(' AND ', $w) . " ORDER BY $o" . ($limite ? ' LIMIT ' . (int)$limite : '');
    $s = $pdo->prepare($sql); $s->execute($a); return $s->fetchAll();
}

function produto_por_slug($pdo, $slug) {
    $s = $pdo->prepare("SELECT p.*, m.nome AS marca, m.slug AS marca_slug, c.nome AS categoria FROM produtos p
        JOIN marcas m ON m.id = p.marca_id JOIN categorias c ON c.id = p.categoria_id WHERE p.slug = ? AND p.ativo = 1");
    $s->execute([$slug]); return $s->fetch();
}

function tamanhos_produto($pdo, $id) {
    $s = $pdo->prepare("SELECT numero, estoque FROM produto_tamanhos WHERE produto_id = ? ORDER BY numero"); $s->execute([$id]); return $s->fetchAll();
}

/* ---------- Checkout ---------- */
function cupom_valor($c, $sub) {
    $c = strtoupper(trim($c));
    if ($c === '') return [0, ''];
    $t = ['LEVE10' => ['%', 10], 'LEVE50' => ['R', 50]]; // edite seus cupons aqui
    if (!isset($t[$c])) return [0, 'Cupom inválido.'];
    [$k, $v] = $t[$c];
    return [$k === '%' ? round($sub * $v / 100, 2) : min($v, $sub), "Cupom $c aplicado!"];
}

function cpf_valido($s) {
    $s = preg_replace('/\D/', '', $s);
    if (strlen($s) != 11 || preg_match('/^(\d)\1+$/', $s)) return false;
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $i = 0; $i < $t; $i++) $d += $s[$i] * ($t + 1 - $i);
        if ((($d * 10) % 11) % 10 != $s[$t]) return false;
    }
    return true;
}

function luhn($n) {
    $n = preg_replace('/\D/', '', $n); $s = 0;
    if (strlen($n) < 13) return false;
    foreach (array_reverse(str_split($n)) as $i => $d) { if ($i % 2) { $d *= 2; if ($d > 9) $d -= 9; } $s += $d; }
    return $s % 10 == 0;
}

function validar_pedido($f) {
    $e = []; $g = fn($k) => trim($f[$k] ?? ''); $dig = fn($k) => preg_replace('/\D/', '', $f[$k] ?? '');
    if (count(preg_split('/\s+/', $g('nome'))) < 2) $e[] = 'Informe o nome completo.';
    if (!cpf_valido($g('cpf'))) $e[] = 'CPF inválido.';
    if (!filter_var($g('email'), FILTER_VALIDATE_EMAIL)) $e[] = 'E-mail inválido.';
    if (!preg_match('/^\d{10,11}$/', $dig('telefone'))) $e[] = 'Telefone inválido.';
    if (strlen($dig('cep')) != 8) $e[] = 'CEP inválido.';
    if (!preg_match('/^[A-Z]{2}$/', $g('estado'))) $e[] = 'Selecione o estado.';
    foreach (['cidade' => 'a cidade', 'bairro' => 'o bairro', 'rua' => 'a rua', 'numero' => 'o número'] as $k => $n) if ($g($k) === '') $e[] = "Informe $n.";
    $p = $g('pagamento');
    if (!in_array($p, ['pix', 'debito', 'credito'])) $e[] = 'Escolha a forma de pagamento.';
    if ($p === 'debito' || $p === 'credito') {
        if ($g('titular') === '') $e[] = 'Informe o nome no cartão.';
        if (!luhn($g('cartao'))) $e[] = 'Número do cartão inválido.';
        if (!preg_match('#^(0[1-9]|1[0-2])/(\d\d)$#', $g('validade'), $m) || (2000 + $m[2]) * 12 + $m[1] < date('Y') * 12 + date('n')) $e[] = 'Validade do cartão inválida.';
        if (!preg_match('/^\d{3,4}$/', $g('cvv'))) $e[] = 'CVV inválido.';
    }
    if ($p === 'credito' && !in_array((int)$g('parcelas'), range(1, 12))) $e[] = 'Parcelas inválidas.';
    return $e;
}

/* Cria cliente, endereço, pedido e itens numa transação. Preço e estoque vêm sempre do banco. */
function criar_pedido(PDO $pdo, array $d, array $itens) {
    $pdo->beginTransaction();
    try {
        $s = $pdo->prepare("SELECT id, senha_hash FROM clientes WHERE email = ?"); $s->execute([trim($d['email'])]); $cl = $s->fetch(); $cid = $cl['id'] ?? 0;
        $dados = [trim($d['nome']), preg_replace('/\D/', '', $d['cpf']), preg_replace('/\D/', '', $d['telefone'])];
        if ($cid) { if (!$cl['senha_hash'] || $cid == ($d['conta_id'] ?? 0)) $pdo->prepare("UPDATE clientes SET nome=?, cpf=?, telefone=? WHERE id=?")->execute([...$dados, $cid]); }
        else { $pdo->prepare("INSERT INTO clientes (nome,cpf,telefone,email) VALUES (?,?,?,?)")->execute([...$dados, trim($d['email'])]); $cid = $pdo->lastInsertId(); }
        $pdo->prepare("INSERT INTO enderecos (cliente_id,cep,estado,cidade,bairro,rua,numero,complemento) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$cid, $d['cep'], $d['estado'], trim($d['cidade']), trim($d['bairro']), trim($d['rua']), trim($d['numero']), trim($d['complemento'] ?? '')]);
        $eid = $pdo->lastInsertId();
        $sub = 0; $linhas = [];
        foreach ($itens as $i) {
            $q = (int)($i['q'] ?? 0);
            $s = $pdo->prepare("SELECT t.estoque, p.nome, p.preco FROM produto_tamanhos t JOIN produtos p ON p.id = t.produto_id WHERE t.produto_id = ? AND t.numero = ? AND p.ativo = 1 FOR UPDATE");
            $s->execute([(int)($i['id'] ?? 0), (int)($i['tam'] ?? 0)]); $r = $s->fetch();
            if (!$r) throw new Exception('Um produto do carrinho não está mais disponível.');
            if ($q < 1 || $q > $r['estoque']) throw new Exception("Estoque insuficiente: {$r['nome']} nº {$i['tam']} (restam {$r['estoque']}).");
            $sub += $r['preco'] * $q; $linhas[] = [(int)$i['id'], (int)$i['tam'], $q, $r['preco']];
        }
        if (!$linhas) throw new Exception('Seu carrinho está vazio.');
        [$desc] = cupom_valor($d['cupom'] ?? '', $sub); $total = $sub - $desc;
        $pdo->prepare("INSERT INTO pedidos (cliente_id,endereco_id,subtotal,desconto,total,cupom,pagamento,parcelas) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$cid, $eid, $sub, $desc, $total, $desc > 0 ? strtoupper(trim($d['cupom'])) : null, $d['pagamento'], $d['pagamento'] === 'credito' ? (int)$d['parcelas'] : 1]);
        $pid = $pdo->lastInsertId();
        foreach ($linhas as [$prod, $tam, $q, $preco]) {
            $pdo->prepare("INSERT INTO itens_pedido (pedido_id,produto_id,tamanho,quantidade,preco_unitario) VALUES (?,?,?,?,?)")->execute([$pid, $prod, $tam, $q, $preco]);
            $pdo->prepare("UPDATE produto_tamanhos SET estoque = estoque - ? WHERE produto_id = ? AND numero = ?")->execute([$q, $prod, $tam]);
        }
        $pdo->commit();
        return [$pid, $total, "{$d['rua']}, {$d['numero']}" . (trim($d['complemento'] ?? '') ? ' ' . trim($d['complemento']) : '') . " - {$d['bairro']}, {$d['cidade']}/{$d['estado']}, CEP {$d['cep']}"];
    } catch (Exception $e) { $pdo->rollBack(); throw $e; }
}
