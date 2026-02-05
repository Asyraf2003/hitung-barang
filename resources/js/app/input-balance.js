function setSubmitDisabled(btn, disabled) {
  if (!btn) return;
  const d = !!disabled;
  btn.disabled = d;
  btn.setAttribute('aria-disabled', d ? 'true' : 'false');
  btn.classList.toggle('opacity-50', d);
  btn.classList.toggle('pointer-events-none', d);
}

function fmtKg(x) {
  const n = Number(x);
  if (!Number.isFinite(n)) return '0,00';
  return n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function showHintBox(hintEl, msg, tone = 'muted') {
  if (!hintEl) return;

  hintEl.classList.remove('hidden');
  hintEl.classList.remove(
    'border-slate-200', 'bg-slate-50', 'text-slate-600',
    'border-rose-200', 'bg-rose-50', 'text-rose-700',
    'border-[#118EEA]/20', 'bg-[#118EEA]/10', 'text-[#118EEA]'
  );

  // base box style (kalau belum ada di blade)
  hintEl.classList.add('rounded-xl', 'border', 'px-3', 'py-2');

  if (tone === 'danger') hintEl.classList.add('border-rose-200', 'bg-rose-50', 'text-rose-700');
  else if (tone === 'info') hintEl.classList.add('border-[#118EEA]/20', 'bg-[#118EEA]/10', 'text-[#118EEA]');
  else hintEl.classList.add('border-slate-200', 'bg-slate-50', 'text-slate-600');

  hintEl.textContent = msg;
}

function hideHintBox(hintEl) {
  if (!hintEl) return;
  hintEl.classList.add('hidden');
  hintEl.textContent = '';
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

  function updateSubmitState() {
    const type = currentType();
    const q = currentQty();

    let disabled = false;

    if (type === 'OUT') {
      if (balanceKg === null) {
        disabled = true;
        showHintBox(hintEl, 'Pilih tipe dulu untuk cek saldo.', 'muted');
      } else if (balanceKg <= 0) {
        disabled = true;
        showHintBox(hintEl, 'Saldo 0. Tidak bisa keluar.', 'danger');
      } else if (q <= 0) {
        disabled = true;
        showHintBox(hintEl, `Saldo: ${fmtKg(balanceKg)} kg. Masukkan berat.`, 'info');
      } else if (q > balanceKg) {
        disabled = true;
        showHintBox(hintEl, `Stok tidak cukup. Tersedia ${fmtKg(balanceKg)} kg.`, 'danger');
      } else {
        const remaining = Math.max(0, balanceKg - q);
        showHintBox(
          hintEl,
          `Saldo: ${fmtKg(balanceKg)} kg • Sisa setelah keluar: ${fmtKg(remaining)} kg`,
          'info'
        );
      }
    } else {
      // IN
      if (q <= 0) {
        disabled = true;
        showHintBox(hintEl, 'Masukkan berat dulu.', 'muted');
      } else {
        hideHintBox(hintEl);
      }
    }

    setSubmitDisabled(submitBtn, disabled);
  }

  async function fetchJsonNoStore(url) {
    const u = new URL(url, window.location.origin);
    u.searchParams.set('_t', String(Date.now()));
    const res = await fetch(u.toString(), {
      headers: { 'Accept': 'application/json' },
      cache: 'no-store',
    });
    const data = await res.json().catch(() => null);
    if (!res.ok || !data || data.ok !== true) return null;
    return data;
  }

  async function refreshBalance() {
    const mySeq = ++reqSeq;

    if (currentType() !== 'OUT') {
      balanceKg = null;
      hideHintBox(hintEl);
      updateSubmitState();
      return;
    }

    balanceKg = null;
    showHintBox(hintEl, 'Mengecek saldo…', 'muted');
    updateSubmitState();

    const useNew = !!(useNewBox && useNewBox.checked);
    const itemId = itemSelect?.value || '';

    try {
      // OUT + pilih tipe existing
      if (!useNew && typeSelect && typeSelect.value) {
        const data = await fetchJsonNoStore(`${balanceUrl}?item_type_id=${encodeURIComponent(typeSelect.value)}`);
        if (mySeq !== reqSeq) return;

        if (!data) {
          showHintBox(hintEl, 'Gagal cek saldo. Coba lagi.', 'danger');
          updateSubmitState();
          return;
        }

        balanceKg = Number(data.balance_kg) || 0;
        updateSubmitState();
        return;
      }

      // OUT + tipe baru (diameter) + pilih item
      if (useNew && itemId && diameterInput && diameterInput.value.trim() !== '') {
        const data = await fetchJsonNoStore(
          `${balanceUrl}?item_id=${encodeURIComponent(itemId)}&diameter_mm=${encodeURIComponent(diameterInput.value.trim())}`
        );
        if (mySeq !== reqSeq) return;

        if (!data) {
          showHintBox(hintEl, 'Gagal cek saldo. Coba lagi.', 'danger');
          updateSubmitState();
          return;
        }

        balanceKg = Number(data.balance_kg) || 0;
        updateSubmitState();
        return;
      }

      // OUT tapi belum lengkap pilihannya
      balanceKg = null;
      updateSubmitState();
    } catch {
      if (mySeq !== reqSeq) return;
      showHintBox(hintEl, 'Gagal cek saldo (network).', 'danger');
      updateSubmitState();
    }
  }

  // events
  itemSelect?.addEventListener('change', refreshBalance);

  // kalau pilih tipe existing: auto sync item dari data-item-id (kalau ada)
  typeSelect?.addEventListener('change', () => {
    const opt = typeSelect.options[typeSelect.selectedIndex];
    const itemId = opt?.dataset?.itemId;
    if (itemId && itemSelect && itemSelect.value !== itemId) itemSelect.value = itemId;
    refreshBalance();
  });

  let diamT = null;
  diameterInput?.addEventListener('input', () => {
    clearTimeout(diamT);
    diamT = setTimeout(refreshBalance, 300);
  });

  useNewBox?.addEventListener('change', refreshBalance);

  qtyInput?.addEventListener('input', () => {
    updateSubmitState();
  });

  form.querySelectorAll('input[name="type"]').forEach((el) => {
    el.addEventListener('change', () => {
      updateSubmitState();
      refreshBalance();
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
