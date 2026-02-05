@php
  $filters = $filters ?? ['q'=>'','type'=>'','item_type_id'=>'','from'=>'','to'=>'','show_voided'=>''];
  $type_options = $type_options ?? collect();
  $showVoided = ($filters['show_voided'] ?? '') === '1';
@endphp

<div class="space-y-3">
  <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
    <div class="text-base font-semibold">Riwayat</div>
    <div class="mt-1 text-sm text-slate-500">Cari & filter transaksi.</div>

    <form method="GET" action="/app/history" data-pjax-form class="mt-4 space-y-3">
      <div>
        <input name="q" value="{{ $filters['q'] }}" placeholder="Cari (catatan / barang / tipe)"
          class="w-full rounded-xl bg-white border border-slate-200 px-3 py-2 outline-none
                 focus:border-[#118EEA] focus:ring-2 focus:ring-[#118EEA]/20">
      </div>

      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="text-xs text-slate-500">Dari</label>
          <input type="date" name="from" value="{{ $filters['from'] }}"
            class="mt-1 w-full rounded-xl bg-white border border-slate-200 px-3 py-2 outline-none
                  focus:border-[#118EEA] focus:ring-2 focus:ring-[#118EEA]/20">
        </div>
        <div>
          <label class="text-xs text-slate-500">Sampai</label>
          <input type="date" name="to" value="{{ $filters['to'] }}"
            class="mt-1 w-full rounded-xl bg-white border border-slate-200 px-3 py-2 outline-none
                   focus:border-[#118EEA] focus:ring-2 focus:ring-[#118EEA]/20">
        </div>
      </div>

      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="text-xs text-slate-500">Tipe Transaksi</label>
          <select name="type"
            class="mt-1 w-full rounded-xl bg-white border border-slate-200 px-3 py-2 outline-none
                   focus:border-[#118EEA] focus:ring-2 focus:ring-[#118EEA]/20">
            <option value="">Semua</option>
            <option value="IN" {{ $filters['type']==='IN'?'selected':'' }}>Masuk</option>
            <option value="OUT" {{ $filters['type']==='OUT'?'selected':'' }}>Keluar</option>
            <option value="ADJUST" {{ $filters['type']==='ADJUST'?'selected':'' }}>Adjust</option>
          </select>
        </div>

        <div>
          <label class="text-xs text-slate-500">Tipe Barang</label>
          <select name="item_type_id"
            class="mt-1 w-full rounded-xl bg-white border border-slate-200 px-3 py-2 outline-none
                   focus:border-[#118EEA] focus:ring-2 focus:ring-[#118EEA]/20">
            <option value="">Semua</option>
            @foreach($type_options as $opt)
              <option value="{{ $opt['id'] }}" {{ (string)$filters['item_type_id']===(string)$opt['id']?'selected':'' }}>
                {{ $opt['label'] }}
              </option>
            @endforeach
          </select>
        </div>
      </div>

      <label class="flex items-center justify-between rounded-xl border border-slate-200 px-3 py-2 cursor-pointer">
        <div class="text-xs font-semibold text-slate-600">Tampilkan dibatalkan</div>
        <input type="checkbox" name="show_voided" value="1" {{ $showVoided ? 'checked' : '' }}>
      </label>

      <div class="grid grid-cols-2 gap-2">
        <button class="rounded-xl bg-[#118EEA] py-2.5 font-semibold text-white shadow-sm active:scale-[0.99]">
          Terapkan
        </button>

        <a data-pjax href="/app/history"
          class="rounded-xl border border-slate-200 py-2.5 font-semibold text-slate-700 text-center">
          Reset
        </a>
      </div>
    </form>
  </div>

  @if(empty($rows) || count($rows) === 0)
    <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm text-sm text-slate-500">
      Tidak ada hasil.
    </div>
  @else
    <div class="space-y-2">
      @foreach($rows as $r)
        @php
          $isVoided = !empty($r['is_voided']);
          $isIn = ($r['type'] ?? '') === 'IN';
          $typeLabel = $isIn ? 'Masuk' : (($r['type'] ?? '') === 'OUT' ? 'Keluar' : 'Adjust');

          $badgeClass = $isIn ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                      : (($r['type'] ?? '')==='OUT' ? 'bg-rose-50 text-rose-700 border-rose-200'
                                                    : 'bg-slate-50 text-slate-700 border-slate-200');

          $hasCorrection = !empty($r['corrects_id']);
        @endphp

        <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm {{ $isVoided ? 'opacity-60' : '' }}">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full border {{ $badgeClass }}">
                  {{ $typeLabel }}
                </span>

                @if($hasCorrection)
                  <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-semibold rounded-full border bg-[#118EEA]/10 text-[#118EEA] border-[#118EEA]/20">
                    Koreksi
                  </span>
                @endif

                @if($isVoided)
                  <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-semibold rounded-full border bg-slate-50 text-slate-600 border-slate-200">
                    Dibatalkan
                  </span>
                @endif

                <div class="text-sm text-slate-600 truncate">
                  {{ $r['barang'] ?? '-' }} • {{ $r['tipe'] ?? '-' }}
                </div>
              </div>

              <div class="mt-2 text-xs text-slate-400">
                {{ $r['occurred_at'] ?? '-' }}
              </div>

              @if(!empty($r['note']))
                <div class="mt-2 text-sm text-slate-600">
                  {{ $r['note'] }}
                </div>
              @endif

              @if($isVoided)
                @if(!empty($r['voided_at']))
                  <div class="mt-3 pt-3 border-t border-slate-100 text-xs text-slate-400">
                    Dikoreksi pada: {{ $r['voided_at'] }}
                  </div>
                @endif
              @else
                <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <button
                      type="button"
                      data-action="edit-move"
                      data-id="{{ $r['id'] }}"
                      data-qty="{{ $r['qty_kg'] }}"
                      data-note="{{ $r['note'] ?? '' }}"
                      class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-[#118EEA] transition-all hover:bg-blue-100 active:scale-95">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                      </svg>
                      Edit
                    </button>

                    <button
                      type="button"
                      data-action="delete-move"
                      data-id="{{ $r['id'] }}"
                      class="inline-flex items-center gap-1.5 rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600 transition-all hover:bg-rose-100 active:scale-95">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                      Hapus
                    </button>
                  </div>
                </div>
              @endif
            </div>

            <div class="text-right shrink-0">
              <div class="text-xs text-slate-500">Berat</div>
              <div class="text-lg font-semibold">
                {{ number_format((float)($r['qty_kg'] ?? 0), 2, ',', '.') }} kg
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>

{{-- Modal Edit --}}
<div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 transition-opacity duration-300 opacity-0">
  <div id="modal-bg" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>

  <div id="modal-content" class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl transition-all duration-300 scale-95 opacity-0">
    <h3 class="text-lg font-semibold text-slate-800">Edit Transaksi</h3>

    <div class="mt-4 space-y-4">
      <input type="hidden" id="edit_id">
      <div>
        <label class="text-xs text-slate-500 font-medium">Berat (kg)</label>
        <input type="number" id="edit_qty" step="0.01"
          class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 outline-none focus:border-[#118EEA]">
      </div>
      <div>
        <label class="text-xs text-slate-500 font-medium">Catatan</label>
        <textarea id="edit_note" rows="3"
          class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 outline-none focus:border-[#118EEA]"></textarea>
      </div>
    </div>

    <div class="mt-6 grid grid-cols-2 gap-3">
      <button type="button" data-action="close-modal"
        class="rounded-xl border border-slate-200 py-2.5 font-semibold text-slate-600 active:scale-95">Batal</button>
      <button type="button" id="btn-submit-edit"
        class="rounded-xl bg-[#118EEA] py-2.5 font-semibold text-white shadow-sm active:scale-95">Simpan</button>
    </div>
  </div>
</div>
