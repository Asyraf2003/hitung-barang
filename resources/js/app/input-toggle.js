function initMoveTypeToggle(root = document) {
  const form = root.querySelector('#moveForm');
  if (!form) return;

  const boxes = Array.from(form.querySelectorAll('[data-move-type-box]'));
  const radios = Array.from(form.querySelectorAll('input[name="type"]'));

  const apply = () => {
    const current = form.querySelector('input[name="type"]:checked')?.value || 'IN';

    boxes.forEach((box) => {
      const val = box.getAttribute('data-move-type-box');
      const active = val === current;

      box.classList.toggle('border-[#118EEA]', active);
      box.classList.toggle('bg-[#118EEA]/10', active);
      box.classList.toggle('text-[#118EEA]', active);

      box.classList.toggle('border-slate-200', !active);
      box.classList.toggle('text-slate-600', !active);
    });
  };

  radios.forEach((r) => r.addEventListener('change', apply));
  apply();
}

document.addEventListener('DOMContentLoaded', () => initMoveTypeToggle(document));

document.addEventListener('pjax:loaded', (e) => {
  const { url, root } = e.detail || {};
  if (!url || !root) return;
  if (url.startsWith('/app/input')) initMoveTypeToggle(root);
});
