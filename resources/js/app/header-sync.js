function syncHeader(root = document) {
  const appMain = document.getElementById('appMain');
  if (!appMain) return;

  // cari metadata di konten yg aktif
  const src = appMain.querySelector('[data-page-header]');
  if (!src) return;

  const title = src.getAttribute('data-title') || '';
  const meta  = src.getAttribute('data-meta') || '';

  const ht = document.getElementById('headerTitle');
  const hm = document.getElementById('headerMeta');

  if (ht && title) ht.textContent = title;

  if (hm) {
    if (meta) {
      hm.textContent = meta;
      hm.classList.remove('hidden');
    } else {
      hm.textContent = '';
      hm.classList.add('hidden');
    }
  }
}

function initHeaderSync() {
  const appMain = document.getElementById('appMain');
  if (!appMain) return;

  // jalan sekali untuk initialHtml
  syncHeader();

  // auto update setiap PJAX swap (tanpa nebak event PJAX)
  let raf = 0;
  const obs = new MutationObserver(() => {
    cancelAnimationFrame(raf);
    raf = requestAnimationFrame(() => syncHeader());
  });

  obs.observe(appMain, { childList: true, subtree: true });
}

document.addEventListener('DOMContentLoaded', initHeaderSync);
