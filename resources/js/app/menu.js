function initMenu(root = document) {
  const btn = root.querySelector('#btnMenu') || document.querySelector('#btnMenu');
  const overlay = root.querySelector('#menuOverlay') || document.querySelector('#menuOverlay');
  if (!btn || !overlay) return;

  const open = () => overlay.classList.remove('hidden');
  const close = () => overlay.classList.add('hidden');

  btn.onclick = (e) => {
    e.preventDefault();
    overlay.classList.contains('hidden') ? open() : close();
  };

  overlay.onclick = (e) => {
    // klik background = close
    if (e.target === overlay || e.target === overlay.firstElementChild) close();
  };

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') close();
  });
}

document.addEventListener('DOMContentLoaded', () => initMenu(document));
document.addEventListener('pjax:loaded', (e) => {
  const { root } = e.detail || {};
  if (!root) return;
  initMenu(root);
});
