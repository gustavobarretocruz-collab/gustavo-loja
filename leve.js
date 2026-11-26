(() => {
const K = 'leve_cart', $ = s => document.querySelector(s), $$ = s => [...document.querySelectorAll(s)];
const R = n => n.toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
let cupom = {desc: 0};

function toast(msg, tipo = 'ok') {
  const t = document.createElement('div'); t.className = 'toast ' + tipo; t.textContent = msg;
  $('#toasts').appendChild(t); setTimeout(() => t.classList.add('out'), 2600); setTimeout(() => t.remove(), 3000);
}

/* ---------- Carrinho (localStorage; chave = produto + numeração) ---------- */
const get = () => { try { return JSON.parse(localStorage.getItem(K)) || []; } catch (e) { return []; } };
const save = c => { try { localStorage.setItem(K, JSON.stringify(c)); } catch (e) { toast('Não foi possível salvar o carrinho.', 'erro'); } render(); };
const sub = c => c.reduce((s, i) => s + i.preco * i.q, 0), qtd = c => c.reduce((s, i) => s + i.q, 0);
const open = o => { $('#drawer').classList.toggle('open', o); $('#ov').classList.toggle('open', o); };

function add(p, tam, max) {
  const k = p.id + '-' + tam, c = get(), i = c.find(x => x.k === k);
  if (i) { if (i.q >= max) return toast(`Só temos ${max} unidade(s) do nº ${tam}.`, 'erro'); i.q++; i.max = max; }
  else c.push({k, id: p.id, nome: p.nome, marca: p.marca, tam, preco: p.preco, q: 1, max, foto: p.foto});
  save(c); toast(`${p.nome} (nº ${tam}) adicionado ao carrinho!`);
}
function mudar(k, d) {
  const c = get(), i = c.find(x => x.k === k); if (!i) return;
  if (d > 0 && i.q >= i.max) return toast(`Só temos ${i.max} unidade(s) desse número.`, 'erro');
  i.q += d; save(c.filter(x => x.q > 0));
}

function render() {
  const c = get(), s = sub(c), n = qtd(c);
  $('#cbadge').textContent = n; $('#cbadge').classList.toggle('on', n > 0);
  $('#cqtd').textContent = n; $('#csub').textContent = R(s);
  $('#cgo').classList.toggle('dis', !n); $('#cclr').hidden = !n;
  $('#citens').innerHTML = c.length ? c.map(i => `
    <div class="ci"><img src="${esc(i.foto)}" alt=""><div><b>${esc(i.nome)}</b><small>Nº ${i.tam} · ${R(i.preco)}</small>
      <div class="qt"><button data-m="${i.k}" data-d="-1" aria-label="Diminuir">−</button><span>${i.q}</span><button data-m="${i.k}" data-d="1" aria-label="Aumentar">+</button>
      <button class="rm" data-r="${i.k}">Remover</button></div></div><b>${R(i.preco * i.q)}</b></div>`).join('')
    : '<p class="vazio">Seu carrinho está vazio.</p>';
  if ($('#checkout')) renderCheckout(c, s, n);
}

/* ---------- Página do produto ---------- */
function initProduto() {
  const pg = $('#prod'); let sel = null;
  $$('.tam').forEach(b => b.onclick = () => { if (b.disabled) return;
    $$('.tam').forEach(x => x.classList.remove('sel')); b.classList.add('sel'); sel = b; $('#tamlbl').textContent = '(nº ' + b.dataset.n + ')'; });
  $('#addp').onclick = () => { if (!sel) return toast('Selecione a numeração antes de adicionar.', 'erro'); add(JSON.parse(pg.dataset.p), +sel.dataset.n, +sel.dataset.max); };
  const z = $('.zoom'); z.onmousemove = e => { const r = z.getBoundingClientRect();
    z.firstElementChild.style.transformOrigin = `${(e.clientX - r.left) / r.width * 100}% ${(e.clientY - r.top) / r.height * 100}%`; };
}

/* ---------- Checkout ---------- */
function renderCheckout(c, s, n) {
  const desc = Math.min(cupom.desc, s), tot = s - desc;
  $('#rlist').innerHTML = c.length ? c.map(i => `<div class="ri"><img src="${esc(i.foto)}" alt=""><div><b>${esc(i.nome)}</b><small>Nº ${i.tam} · ${i.q} × ${R(i.preco)}</small></div><b>${R(i.preco * i.q)}</b></div>`).join('')
    : '<p class="vazio">Carrinho vazio. <a href="loja.php">Escolher produtos</a></p>';
  $('#rq').textContent = n; $('#rsub').textContent = R(s); $('#rtot').textContent = R(tot);
  $('#rdescl').hidden = !desc; $('#rdesc').textContent = '− ' + R(desc);
  $('#confirmar').disabled = !n;
  const p = $('#parcelas'), at = p.value || 1;
  p.innerHTML = Array.from({length: 12}, (_, k) => `<option value="${k + 1}">${k + 1}x de ${R(tot / (k + 1))} sem juros</option>`).join(''); p.value = at;
  $('#itens').value = JSON.stringify(c.map(i => ({id: i.id, tam: i.tam, q: i.q})));
}

const cpfOk = s => { s = s.replace(/\D/g, ''); if (s.length != 11 || /^(\d)\1+$/.test(s)) return false;
  for (let t = 9; t < 11; t++) { let d = 0; for (let i = 0; i < t; i++) d += s[i] * (t + 1 - i); if ((d * 10 % 11) % 10 != s[t]) return false; } return true; };
const luhn = n => { n = n.replace(/\D/g, ''); if (n.length < 13) return false; let s = 0;
  [...n].reverse().forEach((d, i) => { d = +d; if (i % 2) { d *= 2; if (d > 9) d -= 9; } s += d; }); return s % 10 == 0; };
const validade = v => { const m = v.match(/^(0[1-9]|1[0-2])\/(\d\d)$/), h = new Date(); return m && (2000 + +m[2]) * 12 + +m[1] >= h.getFullYear() * 12 + h.getMonth() + 1; };

function initCheckout() {
  const F = $('#checkout'), f = n => F.elements[n];
  const mask = (n, fn) => f(n).addEventListener('input', e => e.target.value = fn(e.target.value.replace(/\D/g, '')));
  mask('cpf', v => v.slice(0, 11).replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2'));
  mask('telefone', v => v.slice(0, 11).replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{5})(\d{1,4})$/, '$1-$2'));
  mask('cep', v => v.slice(0, 8).replace(/(\d{5})(\d)/, '$1-$2'));
  mask('cartao', v => v.slice(0, 16).replace(/(\d{4})(?=\d)/g, '$1 '));
  mask('validade', v => v.slice(0, 4).replace(/(\d\d)(\d)/, '$1/$2'));
  mask('cvv', v => v.slice(0, 4));

  f('cep').addEventListener('blur', async () => { // preenche o endereço pelo CEP (ViaCEP); se falhar, digite manualmente
    const c = f('cep').value.replace(/\D/g, ''); if (c.length != 8) return;
    try { const d = await (await fetch(`https://viacep.com.br/ws/${c}/json/`)).json();
      if (d.erro) return toast('CEP não encontrado.', 'erro');
      f('estado').value = d.uf; f('cidade').value = d.localidade; f('bairro').value = d.bairro; f('rua').value = d.logradouro; f('numero').focus();
    } catch (e) {} });

  const pay = () => { const v = F.querySelector('[name=pagamento]:checked').value;
    $$('.pay').forEach(l => l.classList.toggle('sel', l.querySelector('input').checked));
    $('#p-pix').hidden = v != 'pix'; $('#p-cartao').hidden = v == 'pix'; $('#lparc').hidden = v != 'credito'; };
  $$('[name=pagamento]').forEach(r => r.addEventListener('change', pay)); pay();

  let seed = 7, rnd = () => (seed = seed * 16807 % 2147483647) / 2147483647, q = '';
  const fin = (x, y) => `<rect x="${x}" y="${y}" width="7" height="7" fill="#111"/><rect x="${x + 1}" y="${y + 1}" width="5" height="5" fill="#fff"/><rect x="${x + 2}" y="${y + 2}" width="3" height="3" fill="#111"/>`;
  for (let y = 0; y < 25; y++) for (let x = 0; x < 25; x++) if (!((x < 8 && y < 8) || (x > 16 && y < 8) || (x < 8 && y > 16)) && rnd() > .5) q += `<rect x="${x}" y="${y}" width="1" height="1" fill="#111"/>`;
  $('#qr').innerHTML = `<svg viewBox="-1 -1 27 27">${q}${fin(0, 0)}${fin(18, 0)}${fin(0, 18)}</svg>`;
  $('#pixcopy').onclick = async () => { try { await navigator.clipboard.writeText($('#pixcode').textContent); toast('Código PIX copiado!'); } catch (e) { toast('Selecione e copie o código manualmente.', 'erro'); } };

  const aplicar = async () => {
    const cod = $('#cupom').value.trim(); if (!cod) { cupom = {desc: 0}; $('#cupomv').value = ''; return render(); }
    try { const d = await (await fetch(`checkout.php?cupom=${encodeURIComponent(cod)}&sub=${sub(get())}`)).json();
      cupom = d.ok ? {desc: d.desc} : {desc: 0}; $('#cupomv').value = d.ok ? cod : ''; toast(d.msg, d.ok ? 'ok' : 'erro'); render();
    } catch (e) { toast('Não foi possível validar o cupom.', 'erro'); } };
  $('#cupomok').onclick = aplicar; $('#cupom').addEventListener('keydown', e => { if (e.key == 'Enter') { e.preventDefault(); aplicar(); } });
  if ($('#cupom').value) aplicar();

  const regras = [['nome', v => v.trim().split(/\s+/).length > 1 && v.trim().length > 4, 'Informe o nome completo.'], ['cpf', cpfOk, 'CPF inválido.'],
    ['email', v => /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v), 'E-mail inválido.'], ['telefone', v => /^\d{10,11}$/.test(v.replace(/\D/g, '')), 'Telefone inválido.'],
    ['cep', v => v.replace(/\D/g, '').length == 8, 'CEP inválido.'], ['estado', v => v, 'Selecione o estado.'], ['cidade', v => v.trim(), 'Informe a cidade.'],
    ['bairro', v => v.trim(), 'Informe o bairro.'], ['rua', v => v.trim(), 'Informe a rua.'], ['numero', v => v.trim(), 'Informe o número.']];
  const card = [['titular', v => v.trim().length > 2, 'Informe o nome no cartão.'], ['cartao', luhn, 'Número do cartão inválido.'], ['validade', validade, 'Validade inválida.'], ['cvv', v => /^\d{3,4}$/.test(v), 'CVV inválido.']];
  F.addEventListener('submit', e => {
    $$('.bad').forEach(x => x.classList.remove('bad'));
    if (!get().length) { e.preventDefault(); return toast('Seu carrinho está vazio.', 'erro'); }
    for (const [n, ok, m] of [...regras, ...(F.querySelector('[name=pagamento]:checked').value != 'pix' ? card : [])])
      if (!ok(f(n).value)) { e.preventDefault(); f(n).classList.add('bad'); f(n).focus(); return toast(m, 'erro'); }
  });
}

/* ---------- Eventos ---------- */
document.addEventListener('click', e => {
  const t = e.target.closest('button,a'); if (!t) return;
  if (t.dataset.m) mudar(t.dataset.m, +t.dataset.d);
  else if (t.dataset.r) { save(get().filter(x => x.k !== t.dataset.r)); toast('Produto removido.'); }
  else if (t.id == 'cbtn') open(true); else if (t.id == 'cx') open(false);
  else if (t.id == 'cclr') { save([]); toast('Carrinho esvaziado.'); }
  else if (t.id == 'cgo' && t.classList.contains('dis')) { e.preventDefault(); toast('Adicione produtos antes de finalizar.', 'erro'); }
});
$('#ov').onclick = () => open(false); document.addEventListener('keydown', e => e.key == 'Escape' && open(false));
window.addEventListener('storage', render);
if ($('#prod')) initProduto();
if ($('#checkout')) initCheckout();
render();
})();
(() => {
  const m = (n, fn) => document.querySelectorAll(`input[name=${n}]`).forEach(i => { if (!i.closest('#checkout')) i.addEventListener('input', e => e.target.value = fn(e.target.value.replace(/\D/g, ''))); });
  m('cpf', v => v.slice(0, 11).replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2'));
  m('telefone', v => v.slice(0, 11).replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{5})(\d{1,4})$/, '$1-$2'));
})();
