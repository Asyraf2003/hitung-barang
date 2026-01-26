import Chart from 'chart.js/auto';

let homeCharts = [];

function destroyHomeCharts() {
  homeCharts.forEach((c) => {
    try { c.destroy(); } catch {}
  });
  homeCharts = [];
}

function parseJsonScript(id, root) {
  const el = (root && root.querySelector && root.querySelector(`#${id}`)) || document.querySelector(`#${id}`);
  if (!el) return null;
  try { return JSON.parse(el.textContent || '{}'); } catch { return null; }
}

function initHomeFilters(root) {
  const itemEl = root.querySelector('#homeFilterItem');
  const typeEl = root.querySelector('#homeFilterType');
  const link = root.querySelector('#homeApplyFilterLink');
  if (!itemEl || !typeEl || !link) return;

  // bind global pjax unlock sekali saja (biar ga numpuk)
  if (!window.__homeFilterPjaxBound) {
    window.__homeFilterPjaxBound = true;

    const unlock = () => {
      window.__homeFilterBusy = false;
      // enable semua select filter home yang sedang ada di DOM
      document.querySelectorAll('#homeFilterItem, #homeFilterType').forEach((el) => {
        try { el.disabled = false; } catch {}
      });
    };

    document.addEventListener('pjax:end', unlock);
    document.addEventListener('pjax:error', unlock);
  }

  let t = null;

  function setBusy(busy) {
    window.__homeFilterBusy = busy;
    itemEl.disabled = busy;
    typeEl.disabled = busy;
  }

  function applyNow() {
    // in-flight guard: 1 request saja
    if (window.__homeFilterBusy) return;

    const params = new URLSearchParams();
    params.set('mode', link.dataset.mode || 'daily');
    params.set('date', link.dataset.date || '');

    const type = typeEl.value || '';
    const item = itemEl.value || '';

    if (type) params.set('item_type_id', type);
    else if (item) params.set('item_id', item);

    link.href = '/app/home?' + params.toString();

    setBusy(true);
    link.click();
  }

  function scheduleApply() {
    clearTimeout(t);
    t = setTimeout(applyNow, 250); // debounce
  }

  itemEl.onchange = () => {
    if (window.__homeFilterBusy) return;
    typeEl.value = '';
    scheduleApply();
  };

  typeEl.onchange = () => {
    if (window.__homeFilterBusy) return;
    scheduleApply();
  };
}

function initHomeCharts(root) {
  destroyHomeCharts();

  initHomeFilters(root);

  const data = parseJsonScript('homeChartData', root);
  if (!data) return;

  // Bar IN/OUT
  const barEl = root.querySelector('#chartInOut');
  if (barEl && data.inOut) {
    homeCharts.push(new Chart(barEl, {
      type: 'bar',
      data: {
        labels: data.inOut.labels || [],
        datasets: [
          { label: 'Masuk (kg)', data: data.inOut.in || [], backgroundColor: '#118EEA' },
          { label: 'Keluar (kg)', data: data.inOut.out || [], backgroundColor: '#E11D48' },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: true } },
        scales: { y: { beginAtZero: true } },
      },
    }));
  }
}

// init pertama kali (direct load)
document.addEventListener('DOMContentLoaded', () => {
  const main = document.querySelector('#appMain') || document;
  initHomeCharts(main);
});

// init tiap PJAX load
document.addEventListener('pjax:loaded', (e) => {
  const { url, root } = e.detail || {};
  if (!url || !root) return;
  if (url.startsWith('/app/home')) initHomeCharts(root);
});
