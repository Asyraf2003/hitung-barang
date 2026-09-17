function qs(sel, root = document) {
  return root.querySelector(sel);
}

function toFullPath(input) {
  const u = new URL(String(input || ''), window.location.origin);
  return u.pathname + u.search + u.hash;
}

function navKey(input) {
  const pathname = new URL(String(input || ''), window.location.origin).pathname;

  // Detail laporan tetap milik tab Laporan.
  if (pathname.startsWith('/app/reports')) return '/app/reports';

  return pathname;
}

function setActiveNav(url) {
  const key = navKey(url);

  document.querySelectorAll('[data-nav]').forEach((a) => {
    const active = a.getAttribute('data-nav') === key;

    // Bersihkan class dari implementasi active-nav lama agar hanya ada satu owner.
    a.classList.remove('bg-[#118EEA]/10', 'text-[#118EEA]', 'font-semibold');
    a.classList.toggle('text-white', active);
    a.classList.toggle('text-white/70', !active);
    a.classList.toggle('font-bold', active);

    if (active) {
      a.setAttribute('aria-current', 'page');
    } else {
      a.removeAttribute('aria-current');
    }
  });
}

export function initPJAX() {
  const main = qs('#appMain');
  if (!main) return;

  if (window.__PJAX_INITED__) return;
  window.__PJAX_INITED__ = true;

  const loadingBar = qs('#pjaxLoading');
  const exitModal = qs('#exitModal');
  const exitYes = qs('#exitYes');
  const exitNo = qs('#exitNo');

  const current = window.location.pathname + window.location.search + window.location.hash;
  const startedAtShell = window.location.pathname === '/app';
  const desiredFull = startedAtShell ? '/app/home' : toFullPath(current);
  let lastUrl = desiredFull;

  let busy = false;

  const setLoading = (on) => {
    if (!loadingBar) return;
    loadingBar.classList.toggle('hidden', !on);
  };

  async function load(url, { push = true } = {}) {
    url = toFullPath(url);

    window.__pjaxLoad = (u, opts) => load(u, opts);
    window.__pjaxReload = () => load(lastUrl, { push: false });

    if (busy) return;
    busy = true;
    setLoading(true);

    document.dispatchEvent(new CustomEvent('pjax:start', {
      detail: { url, root: main }
    }));

    try {
      const res = await fetch(url, {
        headers: { 'X-PJAX': '1', 'Accept': 'text/html' },
      });

      if (!res.ok) throw new Error(`Fetch ${url} failed (${res.status})`);

      main.innerHTML = await res.text();
      lastUrl = url;

      // URL, UI, dan bottom-nav harus berpindah sebagai satu state.
      if (push) history.pushState({ __pjax: true, url }, '', url);
      setActiveNav(url);

      document.dispatchEvent(new CustomEvent('pjax:loaded', {
        detail: { url, root: main }
      }));

      document.dispatchEvent(new CustomEvent('pjax:end', {
        detail: { url, root: main }
      }));
    } catch (err) {
      console.error(err);

      document.dispatchEvent(new CustomEvent('pjax:error', {
        detail: { url, root: main, error: err }
      }));

      window.location.href = url;
    } finally {
      setLoading(false);
      busy = false;
    }
  }

  function showExitConfirm() {
    if (!exitModal) {
      history.back();
      return;
    }

    exitModal.classList.remove('hidden');

    const cleanup = () => {
      exitModal.classList.add('hidden');
      exitYes?.removeEventListener('click', onYes);
      exitNo?.removeEventListener('click', onNo);
    };

    const onYes = () => {
      cleanup();
      history.back();
    };

    const onNo = () => {
      cleanup();
      history.pushState({ __pjax: true, url: lastUrl }, '', lastUrl);
      load(lastUrl, { push: false });
    };

    exitYes?.addEventListener('click', onYes);
    exitNo?.addEventListener('click', onNo);
  }

  document.addEventListener('click', (e) => {
    const a = e.target.closest('a[data-pjax]');
    if (!a) return;

    const href = a.getAttribute('href');
    if (!href || href.startsWith('http') || href.startsWith('#')) return;

    e.preventDefault();
    load(href);
  });

  document.addEventListener('submit', (e) => {
    const form = e.target.closest('form[data-pjax-form]');
    if (!form) return;

    e.preventDefault();

    const action = form.getAttribute('action') || window.location.pathname;
    const fd = new FormData(form);
    const params = new URLSearchParams();

    for (const [k, v] of fd.entries()) {
      const val = (v ?? '').toString().trim();
      if (val !== '') params.append(k, val);
    }

    const url = params.toString() ? `${action}?${params.toString()}` : action;
    load(url);
  });

  window.addEventListener('popstate', (e) => {
    if (e.state && e.state.__pjax && e.state.url) {
      load(e.state.url, { push: false });
      return;
    }

    showExitConfirm();
  });

  // Sisipkan entry /app sebagai pintu keluar, lalu simpan halaman aktif di atasnya.
  if (!startedAtShell) {
    history.replaceState(null, '', '/app');
  }
  history.pushState({ __pjax: true, url: desiredFull }, '', desiredFull);
  setActiveNav(desiredFull);

  const hasInitialHtml = (main.innerHTML || '').trim().length > 0;

  if (hasInitialHtml && !startedAtShell) {
    // Direct load /app/{page}: HTML sudah diberikan server, tidak perlu fetch kedua kali.
    document.dispatchEvent(new CustomEvent('pjax:loaded', {
      detail: { url: desiredFull, root: main }
    }));

    document.dispatchEvent(new CustomEvent('pjax:end', {
      detail: { url: desiredFull, root: main }
    }));
  } else {
    load(desiredFull, { push: false });
  }
}

document.addEventListener('DOMContentLoaded', initPJAX);
