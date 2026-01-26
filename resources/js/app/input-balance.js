function setSubmitDisabled(btn, disabled) {
  if (!btn) return;
  btn.disabled = !!disabled;
  btn.classList.toggle('opacity-50', !!disabled);
  btn.classList.toggle('pointer-events-none', !!disabled);
}

function initInputBalance(root) {
  const form = root.querySelector('#moveForm');
  if (!form) return;

  if (form.dataset.balanceBound === '1') return;
  form.dataset.balanceBound = '1';

  const hintEl = form.querySelector('#balanceHint');
  const submitBtn = form.querySelector('#submitMove');

  const balanceUrl = form.getAttribute('data-balance-url') || '/app/api/balance';

  const itemSelect = form.querySelector('#itemSelect');
  const typeSelect = form.querySelector('#itemTypeSelect');
  const diameterInput = form.querySelector('#diameterInput');
  const useNewBox = form.querySelector('input[name="use_new_type"]');
  const qtyInput = form.querySelector('#qtyInput');

  let balanceKg = null;
  let reqSeq = 0;

  function currentType() {
    return form.querySelector('input[name="type"]:checked')?.value || 'IN';
  }

  function currentQty() {
    const raw = (qtyInput?.value || '').trim().replace(',', '.');
    const n = Number(raw);
    return Number.isFinite(n) ? n : 0;
  }

  function fmt(x) {
    const n = Number(x);
    if (!Number.isFinite(n)) return '0,00';
    return n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function showHint(msg, tone = 'muted') {
    hintEl.classList.remove('hidden', 'text-slate-500', 'text-rose-700');
    hintEl.classList.add(tone === 'danger' ? 'text-rose-700' : 'text-slate-500');
    hintEl.textContent = msg;
  }
  function hideHint() { hintEl.classList.add('hidden'); hintEl.textContent = ''; }

  function updateSubmitState() {
    const type = currentType();
    const q = currentQty();

    let disabled = false;

    if (type === 'OUT') {
      if (balanceKg === null) disabled = true;
      else if (balanceKg <= 0) disabled = true;
      else if (q > 0 && q > balanceKg) disabled = true;
      else if (q <= 0) disabled = true;
    } else {
      if (q <= 0) disabled = true;
    }

    setSubmitDisabled(submitBtn, disabled);
  }

  async function fetchJsonNoStore(url) {
    const u = new URL(url, window.location.origin);
    u.searchParams.set('_t', String(Date.now()));
    const res = await fetch(u.toString(), { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
    const data = await res.json().catch(() => null);
    if (!res.ok || !data || data.ok !== true) return null;
    return data;
  }

  async function refreshBalance() {
    const mySeq = ++reqSeq;
    if (currentType() !== 'OUT') {
      balanceKg = null;
      hideHint();
      updateSubmitState();
      return;
    }
    balanceKg = null;
    hideHint();
    updateSubmitState();

    const useNew = !!(useNewBox && useNewBox.checked);
    const itemId = itemSelect?.value || '';

    if (!useNew && typeSelect && typeSelect.value) {
      const data = await fetchJsonNoStore(`${balanceUrl}?item_type_id=${encodeURIComponent(typeSelect.value)}`);
      if (mySeq !== reqSeq) return;
      if (!data) return;

      balanceKg = Number(data.balance_kg) || 0;
      showHint(`Saldo tersedia: ${fmt(balanceKg)} kg`);
      updateSubmitState();
      return;
    }

    if (useNew && itemId && diameterInput && diameterInput.value.trim() !== '') {
      const data = await fetchJsonNoStore(
        `${balanceUrl}?item_id=${encodeURIComponent(itemId)}&diameter_mm=${encodeURIComponent(diameterInput.value.trim())}`
      );
      if (mySeq !== reqSeq) return;
      if (!data) return;

      balanceKg = Number(data.balance_kg) || 0;
      showHint(`Saldo tersedia: ${fmt(balanceKg)} kg`);
      updateSubmitState();
      return;
    }
  }

  // events
  itemSelect?.addEventListener('change', refreshBalance);
  typeSelect?.addEventListener('change', refreshBalance);
    let diamT = null;
  diameterInput?.addEventListener('input', () => {
    clearTimeout(diamT);
    diamT = setTimeout(refreshBalance, 300);
  });
  useNewBox?.addEventListener('change', refreshBalance);

  qtyInput?.addEventListener('input', () => {
    if (currentType() === 'OUT' && balanceKg !== null) {
      const q = currentQty();
      if (q > balanceKg) showHint(`Stok tidak cukup. Tersedia ${fmt(balanceKg)} kg.`, 'danger');
      else showHint(`Saldo tersedia: ${fmt(balanceKg)} kg`);
    }
    updateSubmitState();
  });

  form.querySelectorAll('input[name="type"]').forEach((el) => {
    el.addEventListener('change', () => {
      updateSubmitState();
      if (currentType() === 'OUT') refreshBalance();
    });
  });

  updateSubmitState();
  refreshBalance();
}

document.addEventListener('DOMContentLoaded', () => initInputBalance(document));
document.addEventListener('pjax:loaded', (e) => {
  const { url, root } = e.detail || {};
  if (!url || !root) return;
  const path = new URL(url, window.location.origin).pathname;
  if (path.startsWith('/app/input')) initInputBalance(root);
});
