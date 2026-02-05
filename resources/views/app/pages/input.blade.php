@php
  $type = old('type', request()->query('type', 'IN'));

  $item_options = $item_options ?? collect();
  $type_options = $type_options ?? collect();

  $useNew = old('use_new_type', request()->query('use_new_type', '0')) === '1';

  $prefillItemId = $prefill_item_id ?? null;
  $selectedItem = old('item_id', request()->query('item_id', $prefillItemId ?? ''));
  $selectedType = old('item_type_id', request()->query('item_type_id', ''));

  $useNewItem = old('use_new_item', request()->query('use_new_item', '0')) === '1';
  $newItemName = old('item_name', request()->query('item_name', ''));
  $newItemCode = old('item_code', request()->query('item_code', ''));

  $diam = old('diameter_mm', request()->query('diameter_mm', ''));
@endphp

<div class="space-y-3">
  <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
    <div class="text-base font-semibold">Input</div>
    <div class="mt-1 text-sm text-slate-500">Masuk/Keluar stok. Berat pakai kg (contoh: 0.30).</div>
  </div>

  <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
    <form id="moveForm"
      method="POST"
      action="{{ route('app.moves.store') }}"
      data-balance-url="/app/api/balance"
      class="space-y-3">

      @csrf

      <div class="flex gap-2">
        <label class="flex-1">
          <input type="radio" name="type" value="IN" class="peer sr-only" {{ $type==='IN'?'checked':'' }}>
          <div
            class="rounded-xl border px-3 py-2 text-center font-semibold transition
                  border-slate-200 text-slate-700
                  peer-checked:border-[#118EEA] peer-checked:bg-[#118EEA]/10 peer-checked:text-[#118EEA]">
            Masuk
          </div>
        </label>

        <label class="flex-1">
          <input type="radio" name="type" value="OUT" class="peer sr-only" {{ $type==='OUT'?'checked':'' }}>
          <div
            class="rounded-xl border px-3 py-2 text-center font-semibold transition
                  border-slate-200 text-slate-700
                  peer-checked:border-rose-300 peer-checked:bg-rose-50 peer-checked:text-rose-700">
            Keluar
          </div>
        </label>
      </div>

      @error('type') <div class="text-xs text-red-600">{{ $message }}</div> @enderror

      <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm space-y-3">
        {{-- BARANG --}}
        <div class="flex items-center justify-between">
          <div class="text-sm font-semibold text-slate-600">Barang</div>

          <label class="text-xs font-semibold text-slate-600 flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="use_new_item" value="1" {{ $useNewItem ? 'checked' : '' }}>
            Tambah baru
          </label>
        </div>

        {{-- pilih dari list --}}
        <div id="itemSelectWrap" class="{{ $useNewItem ? 'hidden' : '' }}">
          <label class="text-xs text-slate-500">Pilih barang</label>
          <select id="itemSelect" name="item_id"
            class="mt-1 w-full rounded-xl bg-white border border-slate-200 px-3 py-2 outline-none
                  focus:border-[#118EEA] focus:ring-2 focus:ring-[#118EEA]/20">
            <option value="">-- pilih --</option>
            @foreach($item_options as $opt)
              <option value="{{ $opt['id'] }}" {{ (string)$selectedItem===(string)$opt['id']?'selected':'' }}>
                {{ $opt['name'] }}
              </option>
            @endforeach
          </select>
          @error('item_id') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
        </div>

        {{-- input manual barang baru --}}
        <div id="itemInputWrap" class="{{ $useNewItem ? '' : 'hidden' }} space-y-2">
          <div>
            <label class="text-xs text-slate-500">Nama barang baru</label>
            <input name="item_name" value="{{ $newItemName }}"
              class="mt-1 w-full rounded-xl bg-white border border-slate-200 px-3 py-2 outline-none
                    focus:border-[#118EEA] focus:ring-2 focus:ring-[#118EEA]/20"
              placeholder="contoh: Kawat Galvanis">
            @error('item_name') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
          </div>

          <div>
            <label class="text-xs text-slate-500">Kode (opsional, unik)</label>
            <input name="item_code" value="{{ $newItemCode }}"
              class="mt-1 w-full rounded-xl bg-white border border-slate-200 px-3 py-2 outline-none
                    focus:border-[#118EEA] focus:ring-2 focus:ring-[#118EEA]/20"
              placeholder="contoh: WIRE_DBS">
            @error('item_code') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
          </div>
        </div>

        {{-- TIPE BARANG --}}
        <div class="flex items-center justify-between pt-1">
          <div class="text-sm font-semibold">Tipe Barang</div>

          <label class="text-xs font-semibold text-slate-600 flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="use_new_type" value="1" {{ $useNew ? 'checked' : '' }}>
            Tambah baru
          </label>
        </div>

        {{-- pilih dari list --}}
        <div id="typeSelectWrap" class="{{ $useNew ? 'hidden' : '' }}">
          <label class="text-xs text-slate-500">Pilih tipe</label>
          <select id="itemTypeSelect" name="item_type_id"
            class="mt-1 w-full rounded-xl bg-white border border-slate-200 px-3 py-2 outline-none
                  focus:border-[#118EEA] focus:ring-2 focus:ring-[#118EEA]/20">
            <option value="">-- pilih --</option>
            @foreach($type_options as $opt)
              <option value="{{ $opt['id'] }}"
                data-item-id="{{ $opt['item_id'] }}"
                {{ (string)$selectedType===(string)$opt['id']?'selected':'' }}>
                {{ $opt['label'] }}
              </option>
            @endforeach
          </select>
          @error('item_type_id') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
        </div>

        {{-- input manual (khusus kawat: diameter mm) --}}
        <div id="typeInputWrap" class="{{ $useNew ? '' : 'hidden' }}">
          <label class="text-xs text-slate-500">Tipe baru (diameter mm)</label>
          <input id="diameterInput" name="diameter_mm" value="{{ $diam }}" inputmode="decimal"
            class="mt-1 w-full rounded-xl bg-white border border-slate-200 px-3 py-2 outline-none
                  focus:border-[#118EEA] focus:ring-2 focus:ring-[#118EEA]/20"
            placeholder="contoh: 0.20">
          @error('diameter_mm') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
        </div>
      </div>

      <div>
        <label class="text-sm text-slate-600">Berat</label>

        <div class="relative mt-1">
          <input id="qtyInput" name="qty_kg" value="{{ old('qty_kg') }}" inputmode="decimal"
            class="w-full rounded-xl bg-white border border-slate-200 px-3 py-2 pr-12 outline-none
                  focus:border-[#118EEA] focus:ring-2 focus:ring-[#118EEA]/20"
            placeholder="contoh: 0,30" required>
          <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-500">kg</div>
        </div>

        <div id="balanceHint" class="hidden mt-2 rounded-xl border px-3 py-2 text-xs" role="status" aria-live="polite"></div>
        @error('qty_kg') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
      </div>

      <div>
        <label class="text-sm text-slate-600">Catatan (opsional)</label>
        <textarea name="note" rows="2"
          class="mt-1 w-full rounded-xl bg-white border border-slate-200 px-3 py-2 outline-none
                 focus:border-[#118EEA] focus:ring-2 focus:ring-[#118EEA]/20"
          placeholder="misal: stok awal / pemakaian harian">{{ old('note') }}</textarea>
        @error('note') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
      </div>

      <div class="sticky bottom-0 -mx-4 mt-4 border-t border-slate-200 bg-white/90 backdrop-blur p-4">
        <button
          id="submitMove"
          type="submit"
          class="w-full rounded-xl bg-[#118EEA] py-2.5 font-semibold text-white shadow-sm active:scale-[0.99]
                disabled:bg-[#118EEA]/40 disabled:text-white/80 disabled:shadow-none"
        >
          Simpan
        </button>

        <div id="submitSubtext" class="mt-2 text-center text-[11px] text-slate-500">
          Pastikan barang/tipe & berat sudah benar.
        </div>
      </div>
    </form>
  </div>
</div>
