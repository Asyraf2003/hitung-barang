function getHourInTZ(tz) {
  const parts = new Intl.DateTimeFormat('id-ID', {
    hour: '2-digit',
    hour12: false,
    timeZone: tz,
  }).formatToParts(new Date());

  const h = parts.find(p => p.type === 'hour')?.value ?? '00';
  return parseInt(h, 10);
}

function greetingText(hour) {
  // batasan wajar: pagi 04-10, siang 11-14, sore 15-17, malam 18-03
  if (hour >= 4 && hour <= 10) return 'Selamat Pagi';
  if (hour >= 11 && hour <= 14) return 'Selamat Siang';
  if (hour >= 15 && hour <= 17) return 'Selamat Sore';
  return 'Selamat Malam';
}

function setGreeting(root = document) {
  const el = root.querySelector('#greeting') || document.querySelector('#greeting');
  if (!el) return;

  const hour = getHourInTZ('Asia/Makassar');
  el.textContent = `${greetingText(hour)}`;
}

document.addEventListener('DOMContentLoaded', () => {
  setGreeting(document);
  // update tiap 5 menit, biar kalau lewat batas jam gak stale
  setInterval(() => setGreeting(document), 5 * 60 * 1000);
});

document.addEventListener('pjax:loaded', (e) => {
  const { root } = e.detail || {};
  if (!root) return;
  setGreeting(root);
});
