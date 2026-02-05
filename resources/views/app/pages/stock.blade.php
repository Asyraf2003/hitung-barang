<div class="space-y-3">
  <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
    <div class="text-base font-semibold">Stok</div>
    <div class="mt-1 text-sm text-slate-500">Sisa stok per kg.</div>
  </div>

  @if(empty($items) || count($items) === 0)
    <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm text-sm text-slate-500">
      Belum ada data stok.
    </div>
  @else
    <div class="space-y-2">
      @foreach($items as $it)
        @php
          $balanceKg = (float) ($it['balance_kg'] ?? 0);
          $canOut = ($balanceKg > 0);
        @endphp

        <div class="rounded-2xl bg-white border border-slate-200 px-4 py-3 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="flex items-end justify-between gap-3 min-w-0 flex-1">
              <div class="min-w-0">
                <div class="text-[11px] text-slate-500 truncate uppercase tracking-wider leading-none">
                  {{ $it['barang'] ?? '-' }}
                </div>
                <div class="mt-1 text-sm font-semibold text-slate-900 truncate leading-none">
                  {{ $it['tipe'] ?? '-' }}
                </div>
              </div>

              <div class="shrink-0 text-right">
                <div class="text-[11px] text-slate-500 leading-none">SISA</div>
                <div class="mt-1 text-sm font-semibold text-slate-900 leading-none">
                  {{ number_format($balanceKg, 2, ',', '.') }}
                  <span class="text-sm font-semibold ml-0.5">kg</span>
                </div>
              </div>
            </div>

            <div class="shrink-0 flex items-center gap-2">
              <a data-pjax
                href="/app/input?type=IN&item_type_id={{ $it['item_type_id'] }}&item_id={{ $it['item_id'] }}"
                class="flex items-center justify-center rounded-full border transition-transform bg-[#118EEA]/10 border-[#118EEA]/20 text-[#118EEA] active:scale-95"
                style="width: 44px !important; height: 44px !important; min-width: 44px !important; min-height: 44px !important; flex-shrink: 0 !important; padding: 0 !important;"
                aria-label="Masuk">
                <span style="line-height: 0; font-size: 24px; font-weight: bold;">+</span>
              </a>

              <a data-pjax
                href="{{ $canOut ? ('/app/input?type=OUT&item_type_id='.$it['item_type_id'].'&item_id='.$it['item_id']) : '#' }}"
                class="flex items-center justify-center rounded-full border transition-transform
                  {{ $canOut ? 'bg-rose-50 border-rose-200 text-rose-700' : 'bg-slate-100 border-slate-200 text-slate-400' }}"
                style="width: 44px !important; height: 44px !important; min-width: 44px !important; min-height: 44px !important; flex-shrink: 0 !important; padding: 0 !important;"
                aria-label="Keluar">
                <span style="line-height: 0; font-size: 24px; font-weight: bold;">−</span>
              </a>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>
