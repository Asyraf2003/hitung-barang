@php
  $itemTypeId = (int)($item_type['id'] ?? 0); // ini item_type_id
  $itemId = (int)($item_type['item_id'] ?? request('item_id') ?? 0); // fallback dari query
  $canOut = ((float)($totals['balance_end_kg'] ?? 0)) > 0;
  $from = request('from');
  $backHref = $from === 'stock'
    ? '/app/stock'
    : "/app/reports?mode={$mode}&date={$base_date}";
@endphp

<div class="hidden"
     data-page-header
     data-title="Detail barang"
     data-meta="Semua detail transaksi">
</div>

<div class="space-y-3">
  <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
    {{-- Baris 1: Navigasi Kembali --}}
    <a data-pjax href="{{ $backHref }}" class="inline-flex items-center gap-1 text-[11px] font-bold text-[#118EEA] uppercase tracking-wider active:opacity-50">
        ‹ Kembali
    </a>

    {{-- Baris 2: Header Utama & Aksi --}}
    <div class="mt-2 flex items-center justify-between gap-3">
      <div class="min-w-0">
          <div class="text-base font-bold text-slate-800 leading-tight">
              {{ $item_type['barang'] }}
          </div>
          <div class="mt-0.5 text-sm text-slate-500 leading-tight">
              {{ $item_type['tipe'] }}
          </div>
      </div>

      {{-- Tombol Bulat Aksi (Sinkron dengan gaya Cetak) --}}
      <div class="flex items-center gap-2 shrink-0">
        {{-- Tombol Tambah (+) --}}
        <a data-pjax
          href="/app/input?type=IN&item_type_id={{ $itemTypeId }}&item_id={{ $itemId }}"
          class="flex items-center justify-center rounded-full bg-[#118EEA] text-white shadow-sm active:scale-90 transition-all"
          style="width: 42px; height: 42px; min-width: 42px; min-height: 42px;">
          <span style="line-height: 0; font-size: 22px; font-weight: bold;">+</span>
        </a>

        {{-- Tombol Kurangi (-) - Samakan gaya dengan (+) jika stok ada --}}
        <a data-pjax
          href="{{ $canOut ? "/app/input?type=OUT&item_type_id={$itemTypeId}&item_id={$itemId}" : '#' }}"
          class="flex items-center justify-center rounded-full transition-all 
                  {{ $canOut 
                    ? 'bg-[#118EEA] text-white shadow-sm active:scale-90' 
                    : 'bg-rose-600/20 text-rose-600/40 backdrop-blur-[2px] cursor-not-allowed pointer-events-none border border-rose-200/30' }}"
          style="width: 42px; height: 42px; min-width: 42px; min-height: 42px;">
          <span style="line-height: 0; font-size: 24px; font-weight: bold; margin-top: -2px;">-</span>
        </a>
      </div>
    </div>

    {{-- Baris 3: Info Periode --}}
    <div class="mt-4 pt-3 border-t border-slate-50 text-center">
        <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-widest">
            Periode: {{ $period_label ?? $base_date }}
        </div>
    </div>

    {{-- Baris 4: Ringkasan Angka (Grid 3 Kolom) --}}
    <div class="mt-3 grid grid-cols-3 gap-2">
      <div class="rounded-2xl bg-[#118EEA]/10 border border-[#118EEA]/20 p-2.5 flex flex-col items-center justify-center text-center">
        <div class="text-[10px] text-slate-500 leading-tight uppercase tracking-wide">Masuk</div>
        <div class="mt-1 text-sm font-bold text-[#118EEA] leading-none">
          {{ number_format((float)($totals['in_kg'] ?? 0), 2, ',', '.') }}
        </div>
      </div>

      <div class="rounded-2xl bg-rose-50 border border-rose-200 p-2.5 flex flex-col items-center justify-center text-center">
        <div class="text-[10px] text-slate-500 leading-tight uppercase tracking-wide">Keluar</div>
        <div class="mt-1 text-sm font-bold text-rose-700 leading-none">
          {{ number_format((float)($totals['out_kg'] ?? 0), 2, ',', '.') }}
        </div>
      </div>

      <div class="rounded-2xl border p-2.5 flex flex-col items-center justify-center text-center {{ (float)($totals['balance_end_kg'] ?? 0) >= 0 ? 'bg-emerald-50 border-emerald-200' : 'bg-rose-50 border-rose-200' }}">
        <div class="text-[10px] text-slate-500 leading-tight uppercase tracking-wide">Sisa</div>
        <div class="mt-1 text-sm font-bold leading-none {{ (float)($totals['balance_end_kg'] ?? 0) >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
          {{ number_format((float)($totals['balance_end_kg'] ?? 0), 2, ',', '.') }}
        </div>
      </div>
    </div>
  </div>

  @if(empty($rows) || count($rows) === 0)
    <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm text-sm text-slate-500">
      Tidak ada transaksi di periode ini.
    </div>
  @else
    <div class="space-y-2">
      @foreach($rows as $r)
        @php
          $isIn = $r['type'] === 'IN';
          $label = $isIn ? 'Masuk' : ($r['type']==='OUT' ? 'Keluar' : 'Adjust');
          $badge = $isIn ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                         : ($r['type']==='OUT' ? 'bg-rose-50 text-rose-700 border-rose-200'
                                               : 'bg-slate-50 text-slate-700 border-slate-200');
        @endphp

        <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full border {{ $badge }}">
                {{ $label }}
              </span>

              <div class="mt-2 text-xs text-slate-400">{{ $r['occurred_at'] ?? '-' }}</div>

              @if(!empty($r['note']))
                <div class="mt-2 text-sm text-slate-600">{{ $r['note'] }}</div>
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
