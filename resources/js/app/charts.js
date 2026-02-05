import Chart from 'chart.js/auto';

let homeCharts = [];

function destroyHomeCharts() {
  homeCharts.forEach((c) => {
    try { c.destroy(); } catch {}
  });
  homeCharts = [];
}

function getChartScript(root) {
  return (
    (root && root.querySelector && root.querySelector('#homeChartData')) ||
    document.querySelector('#homeChartData')
  );
}

function parseJsonScript(root) {
  const el = getChartScript(root);
  if (!el) return { data: null, meta: { baseDate: '', mode: '' } };

  const meta = {
    baseDate: el.dataset.baseDate || '',
    mode: el.dataset.mode || '',
  };

  try {
    return { data: JSON.parse(el.textContent || '{}'), meta };
  } catch {
    return { data: null, meta };
  }
}

function initHomeFilters(root) {
  const itemEl = root.querySelector('#homeFilterItem');
  const typeEl = root.querySelector('#homeFilterType');
  const link = root.querySelector('#homeApplyFilterLink');
  if (!itemEl || !typeEl || !link) return;

  if (!window.__homeFilterPjaxBound) {
    window.__homeFilterPjaxBound = true;

    const unlock = () => {
      window.__homeFilterBusy = false;
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
    t = setTimeout(applyNow, 250);
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

function pad2(n) { return String(n).padStart(2, '0'); }

function parseLabelToMillis(label, baseDate) {
  const s = String(label || '').trim();

  // ISO yyyy-mm-dd
  const iso = s.match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if (iso) {
    const t = Date.parse(`${iso[1]}-${iso[2]}-${iso[3]}T00:00:00`);
    return Number.isFinite(t) ? t : null;
  }

  // dd/mm/yyyy
  const dmy = s.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
  if (dmy) {
    const dd = pad2(dmy[1]);
    const mm = pad2(dmy[2]);
    const yy = dmy[3];
    const t = Date.parse(`${yy}-${mm}-${dd}T00:00:00`);
    return Number.isFinite(t) ? t : null;
  }

  // dd/mm (tanpa tahun) -> asumsi tahun dari baseDate, kalau lewat baseDate, geser ke tahun lalu
  const dm = s.match(/^(\d{1,2})\/(\d{1,2})$/);
  if (dm && baseDate) {
    const base = Date.parse(`${baseDate}T00:00:00`);
    if (!Number.isFinite(base)) return null;

    const yyyy = new Date(base).getFullYear();
    const dd = pad2(dm[1]);
    const mm = pad2(dm[2]);

    let t = Date.parse(`${yyyy}-${mm}-${dd}T00:00:00`);
    if (!Number.isFinite(t)) return null;

    // kalau label “lebih besar” dari base (misal base Jan, label 31/12), berarti itu tahun lalu
    if (t > base) {
      t = Date.parse(`${yyyy - 1}-${mm}-${dd}T00:00:00`);
    }
    return Number.isFinite(t) ? t : null;
  }

  return null;
}

function normalizeSeries(series, maxPoints, meta) {
  if (!series) return null;

  const labelsRaw = Array.isArray(series.labels) ? series.labels : [];
  const inRaw = Array.isArray(series.in) ? series.in : [];
  const outRaw = Array.isArray(series.out) ? series.out : [];

  const n = Math.min(labelsRaw.length, inRaw.length, outRaw.length);
  const rows = [];
  for (let i = 0; i < n; i++) {
    rows.push({
      label: labelsRaw[i],
      ms: parseLabelToMillis(labelsRaw[i], meta.baseDate),
      in: Number(inRaw[i] || 0),
      out: Number(outRaw[i] || 0),
    });
  }

  // kalau berhasil parse tanggal, sort by time; kalau tidak, fallback: jangan sok tahu (pakai urutan input)
  const parsedCount = rows.filter((r) => typeof r.ms === 'number').length;
  if (parsedCount >= 2) {
    rows.sort((a, b) => (a.ms ?? 0) - (b.ms ?? 0));
  }

  const sliced = rows.length > maxPoints ? rows.slice(rows.length - maxPoints) : rows;

  return {
    labels: sliced.map((r) => r.label),
    in: sliced.map((r) => r.in),
    out: sliced.map((r) => r.out),
  };
}

function initHomeCharts(root) {
  destroyHomeCharts();
  initHomeFilters(root);

  const { data, meta } = parseJsonScript(root);
  if (!data) return;

  const barEl = root.querySelector('#chartInOut');
  const inOut = normalizeSeries(data.inOut, 5, meta);

  if (barEl && inOut) {
    homeCharts.push(new Chart(barEl, {
      type: 'bar',
      data: {
        labels: inOut.labels,
        datasets: [
          { label: 'Masuk (kg)', data: inOut.in, backgroundColor: '#118EEA' },
          { label: 'Keluar (kg)', data: inOut.out, backgroundColor: '#E11D48' },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: true,
            labels: { boxWidth: 10, boxHeight: 10, font: { size: 11 } },
          },
          tooltip: {
            callbacks: {
              label: (ctx) =>
                `${ctx.dataset.label}: ${Number(ctx.raw || 0).toLocaleString('id-ID', {
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2,
                })} kg`,
            },
          },
        },
        scales: {
          x: {
            ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 5 },
            grid: { display: false },
          },
          y: {
            beginAtZero: true,
            grace: '10%',
            ticks: { callback: (v) => Number(v).toLocaleString('id-ID') },
          },
        },
      },
    }));
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const main = document.querySelector('#appMain') || document;
  initHomeCharts(main);
});

document.addEventListener('pjax:loaded', (e) => {
  const { url, root } = e.detail || {};
  if (!url || !root) return;
  if (url.startsWith('/app/home')) initHomeCharts(root);
});
