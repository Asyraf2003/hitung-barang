function initTypePicker(root = document) {
  const form = root.querySelector('#moveForm');
  if (!form) return;

  // bind-once per form
  if (form.dataset.typePickerBound === '1') return;
  form.dataset.typePickerBound = '1';

  const chk = form.querySelector('input[name="use_new_type"]');
  const wrapSelect = form.querySelector('#typeSelectWrap');
  const wrapInput = form.querySelector('#typeInputWrap');

  const itemSelect = form.querySelector('#itemSelect');
  const typeSelect = form.querySelector('#itemTypeSelect');
  const diam = form.querySelector('#diameterInput');

  if (!chk || !wrapSelect || !wrapInput || !itemSelect || !typeSelect || !diam) return;

  let applying = false;

  function filterTypeOptions() {
    const itemId = itemSelect.value;
    const opts = Array.from(typeSelect.querySelectorAll('option[data-item-id]'));

    opts.forEach((o) => {
      const ok = !itemId || o.getAttribute('data-item-id') === itemId;
      o.hidden = !ok;
    });

    const hiddenSelected = typeSelect.selectedOptions?.[0]?.hidden;
    if (hiddenSelected) typeSelect.value = '';
  }

  function triggerBalanceRefresh() {
    // jangan re-trigger kalau lagi apply (hindari loop)
    // cukup trigger event yang DIDENGAR input-balance.js
    typeSelect.dispatchEvent(new Event('change', { bubbles: true }));

    // hanya trigger diameter input kalau mode "useNew"
    if (chk.checked) {
      diam.dispatchEvent(new Event('input', { bubbles: true }));
    }
  }

  const apply = () => {
    if (applying) return;
    applying = true;

    const useNew = chk.checked;
    wrapSelect.classList.toggle('hidden', useNew);
    wrapInput.classList.toggle('hidden', !useNew);

    filterTypeOptions();

    applying = false;

    // trigger refresh setelah state beres
    triggerBalanceRefresh();
  };

  chk.addEventListener('change', apply);
  itemSelect.addEventListener('change', apply);

  // PENTING: JANGAN pasang typeSelect change -> apply
  // karena apply sendiri memicu typeSelect change untuk refresh balance.

  apply();
}

document.addEventListener('DOMContentLoaded', () => initTypePicker(document));
document.addEventListener('pjax:loaded', (e) => {
  const { url, root } = e.detail || {};
  if (url?.startsWith('/app/input')) initTypePicker(root);
});
