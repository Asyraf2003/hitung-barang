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
  const chkNewItem = form.querySelector('input[name="use_new_item"]');
  const wrapItemSelect = form.querySelector('#itemSelectWrap');
  const wrapItemInput = form.querySelector('#itemInputWrap');

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

    const useNewType = chk.checked;
    wrapSelect.classList.toggle('hidden', useNewType);
    wrapInput.classList.toggle('hidden', !useNewType);

    // Toggle UI "barang baru"
    const useNewItem = !!(chkNewItem && chkNewItem.checked);

    if (wrapItemSelect) wrapItemSelect.classList.toggle('hidden', useNewItem);
    if (wrapItemInput)  wrapItemInput.classList.toggle('hidden', !useNewItem);

    if (useNewItem) {
      // Barang baru belum punya item_id, jadi jangan biarkan select terisi
      if (itemSelect.value) {
        itemSelect.value = '';
      }

      // Karena movement butuh item_type_id, barang baru WAJIB bikin tipe baru
      if (!chk.checked) {
        chk.checked = true;
        wrapSelect.classList.add('hidden');
        wrapInput.classList.remove('hidden');
      }
    }

    filterTypeOptions();

    applying = false;

    // trigger refresh setelah state beres
    triggerBalanceRefresh();
  };

  chk.addEventListener('change', apply);
  itemSelect.addEventListener('change', apply);
  if (chkNewItem) chkNewItem.addEventListener('change', apply);

  // PENTING: JANGAN pasang typeSelect change -> apply
  // karena apply sendiri memicu typeSelect change untuk refresh balance.

  apply();
}

document.addEventListener('DOMContentLoaded', () => initTypePicker(document));
document.addEventListener('pjax:loaded', (e) => {
  const { url, root } = e.detail || {};
  if (url?.startsWith('/app/input')) initTypePicker(root);
});
