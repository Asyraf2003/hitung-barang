function pad2(n) { return String(n).padStart(2, '0'); }
function toIso(d) {
  return `${d.getFullYear()}-${pad2(d.getMonth()+1)}-${pad2(d.getDate())}`;
}

function initHistoryFilterUI(root=document) {
  const btn = root.querySelector('#toggleHistoryFilter');
  const body = root.querySelector('#historyFilterBody');
  const chev = root.querySelector('#historyFilterChevron');
  if (!btn || !body) return;

  const key = 'historyFilterOpen';
  const open = localStorage.getItem(key) === '1';

  function setOpen(v) {
    body.classList.toggle('hidden', !v);
    if (chev) chev.textContent = v ? '▴' : '▾';
    localStorage.setItem(key, v ? '1' : '0');
  }
  setOpen(open);

  btn.addEventListener('click', () => {
    const isOpen = !body.classList.contains('hidden');
    setOpen(!isOpen);
  });

  // quick range buttons
  body.querySelectorAll('[data-range]').forEach((chip) => {
    chip.addEventListener('click', () => {
      const r = chip.getAttribute('data-range');
      const from = body.querySelector('input[name="from"]');
      const to = body.querySelector('input[name="to"]');
      if (!from || !to) return;

      const now = new Date();
      const end = new Date(now.getFullYear(), now.getMonth(), now.getDate());
      const start = new Date(end);

      if (r === 'today') {
        // start already today
      } else {
        const days = parseInt(r, 10);
        if (Number.isFinite(days) && days > 0) start.setDate(start.getDate() - (days - 1));
      }

      from.value = toIso(start);
      to.value = toIso(end);

      // auto submit (biar 1 tap selesai)
      body.requestSubmit?.();
      if (!body.requestSubmit) body.closest('form')?.submit();
    });
  });
}

document.addEventListener('DOMContentLoaded', () => initHistoryFilterUI(document));
document.addEventListener('pjax:loaded', (e) => {
  const { root } = e.detail || {};
  if (root) initHistoryFilterUI(root);
});
