/**
 * History Actions (Edit & Delete) - PJAX safe
 * Pattern: side-effect import (Vite)
 */

let bound = false;

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function setSubmitDisabled(btn, disabled) {
  if (!btn) return;
  btn.disabled = !!disabled;
  btn.classList.toggle('opacity-50', !!disabled);
  btn.classList.toggle('pointer-events-none', !!disabled);
}

async function postJson(url, payload = {}) {
  const token = csrfToken();
  const body = { ...payload, _token: token };

  const res = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': token,
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify(body),
  });

  const data = await res.json().catch(() => null);
  return { res, data };
}

function modalEls() {
  return {
    modal: document.getElementById('editModal'),
    bg: document.getElementById('modal-bg'),
    content: document.getElementById('modal-content'),
    id: document.getElementById('edit_id'),
    qty: document.getElementById('edit_qty'),
    note: document.getElementById('edit_note'),
    submit: document.getElementById('btn-submit-edit'),
  };
}

function openModal() {
  const { modal, content } = modalEls();
  if (!modal || !content) return;

  modal.classList.remove('hidden');
  modal.classList.add('flex');

  requestAnimationFrame(() => {
    modal.classList.add('opacity-100');
    content.classList.remove('scale-95', 'opacity-0');
    content.classList.add('scale-100', 'opacity-100');
  });
}

function closeModal() {
  const { modal, content } = modalEls();
  if (!modal || !content) return;

  modal.classList.remove('opacity-100');
  content.classList.add('scale-95', 'opacity-0');

  setTimeout(() => {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }, 300);
}

function reloadHistoryIfOnIt() {
  const path = location.pathname;
  if (!path.startsWith('/app/history')) return;

  if (typeof window.__pjaxReload === 'function') window.__pjaxReload();
  else window.location.reload();
}

export function initHistoryActions() {
  if (bound) return;
  bound = true;

  // 1) Click actions: edit / delete / close
  document.addEventListener('click', async (e) => {
    // open edit
    const editBtn = e.target.closest('[data-action="edit-move"]');
    if (editBtn) {
      const els = modalEls();
      if (!els.id || !els.qty || !els.note) return;

      els.id.value = editBtn.dataset.id || '';
      els.qty.value = editBtn.dataset.qty || '';
      els.note.value = editBtn.dataset.note || '';

      openModal();
      return;
    }

    // delete
    const delBtn = e.target.closest('[data-action="delete-move"]');
    if (delBtn) {
      const id = delBtn.dataset.id || '';
      if (!id) return;

      const ok = window.confirm('Hapus transaksi ini? Saldo akan dikalkulasi ulang otomatis.');
      if (!ok) return;

      const { res, data } = await postJson(`/app/moves/${encodeURIComponent(id)}/delete`);
      if (res.ok && data && data.ok === true) {
        reloadHistoryIfOnIt();
      } else {
        window.alert((data && data.message) ? data.message : 'Gagal menghapus.');
      }
      return;
    }

    // close modal (button or bg)
    const els = modalEls();
    if (e.target.closest('[data-action="close-modal"]') || (els.bg && e.target === els.bg)) {
      closeModal();
      return;
    }
  });

  // 2) Submit edit (button)
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('#btn-submit-edit');
    if (!btn) return;

    const els = modalEls();
    const id = (els.id?.value || '').trim();
    if (!id) return;

    const qty = (els.qty?.value || '').toString().trim();
    const note = (els.note?.value || '').toString();

    setSubmitDisabled(btn, true);
    const original = btn.textContent;
    btn.textContent = 'Menyimpan...';

    try {
      const { res, data } = await postJson(`/app/moves/${encodeURIComponent(id)}/update`, {
        qty_kg: qty,
        note,
      });

      if (res.ok && data && data.ok === true) {
        closeModal();
        reloadHistoryIfOnIt();
      } else {
        window.alert((data && data.message) ? data.message : 'Gagal menyimpan.');
      }
    } finally {
      btn.textContent = original;
      setSubmitDisabled(btn, false);
    }
  });
}

// side-effect init (consistent with your other modules)
document.addEventListener('DOMContentLoaded', initHistoryActions);
