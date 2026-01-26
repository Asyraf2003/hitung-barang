function qs(sel, root = document) {
  return root.querySelector(sel);
}

function setActiveNav(url) {
  document.querySelectorAll('[data-nav]').forEach((a) => {
    const active = a.getAttribute('data-nav') === url;

    a.classList.toggle('bg-[#118EEA]/10', active);
    a.classList.toggle('text-[#118EEA]', active);
    a.classList.toggle('font-semibold', active);
  });
}

export function initPJAX() {
  const main = qs('#appMain');
  if (!main) return;

  const loadingBar = qs('#pjaxLoading');
  const exitModal = qs('#exitModal');
  const exitYes = qs('#exitYes');
  const exitNo = qs('#exitNo');

  let lastUrl = location.pathname === '/app' ? '/app/home' : location.pathname;
  let busy = false;

  const setLoading = (on) => {
    if (!loadingBar) return;
    loadingBar.classList.toggle('hidden', !on);
  };

  async function load(url, { push = true } = {}) {
    window.__pjaxLoad = (url, opts) => load(url, opts);
    window.__pjaxReload = () => load(lastUrl, { push: false });
    if (busy) return;
    busy = true;
    setLoading(true);

    try {
      const res = await fetch(url, {
        headers: { 'X-PJAX': '1', 'Accept': 'text/html' },
      });

      if (!res.ok) throw new Error(`Fetch ${url} failed (${res.status})`);

      main.innerHTML = await res.text();

      document.dispatchEvent(new CustomEvent('pjax:loaded', {
        detail: { url, root: main }
      }));

      lastUrl = url;

      if (push) history.pushState({ __pjax: true, url }, '', url);
      setActiveNav(url);
    } catch (err) {
      console.error(err);
      window.location.href = url; // fallback hard nav
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
      history.back(); // keluar beneran (ke entry sebelum app)
    };

    const onNo = () => {
      cleanup();
      // tahan user di app: dorong balik ke lastUrl
      history.pushState({ __pjax: true, url: lastUrl }, '', lastUrl);
      load(lastUrl, { push: false });
    };

    exitYes?.addEventListener('click', onYes);
    exitNo?.addEventListener('click', onNo);
  }

  document.addEventListener('click', (e) => {
    const a = e.target.closest('a[data-pjax]');
    if (!a) return;

    const url = a.getAttribute('href');
    if (!url || url.startsWith('http')) return;

    e.preventDefault();
    load(url);
  });

  document.addEventListener('submit', (e) => {
    const form = e.target.closest('form[data-pjax-form]');
    if (!form) return;

    e.preventDefault();

    const action = form.getAttribute('action') || location.pathname;
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
    // state null => user mau keluar dari app
    showExitConfirm();
  });

  // Bikin base history di /app supaya back pertama bisa kita tahan dengan modal
  const desired = location.pathname === '/app' ? '/app/home' : location.pathname;

  if (location.pathname !== '/app') {
    history.replaceState(null, '', '/app');
    history.pushState({ __pjax: true, url: desired }, '', desired);
  } else {
    history.pushState({ __pjax: true, url: desired }, '', desired);
  }

  load(desired, { push: false });
}

document.addEventListener('DOMContentLoaded', initPJAX);

