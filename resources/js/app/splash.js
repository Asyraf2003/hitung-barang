function hideSplash() {
  const el = document.querySelector('#appSplash');
  if (!el) return;

  el.style.transition = 'opacity 180ms ease';
  el.style.opacity = '0';
  setTimeout(() => el.remove(), 200);
}

// direct load
document.addEventListener('DOMContentLoaded', () => {
  // kasih delay dikit biar kerasa "app"
  setTimeout(hideSplash, 250);
});

// tiap PJAX load (optional: tampilkan kecil pas pindah page)
document.addEventListener('pjax:before', () => {
  // kalau kamu mau splash mini setiap pindah page, uncomment:
  // const el = document.querySelector('#appSplash');
  // if (el) return;
});

document.addEventListener('pjax:loaded', () => {
  // pastikan splash hilang (kalau kebetulan masih ada)
  hideSplash();
});
