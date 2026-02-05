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
        @endphp

        <a data-pjax
          href="/app/reports-detail?mode=monthly&date={{ now()->toDateString() }}&item_type_id={{ $it['item_type_id'] }}&item_id={{ $it['item_id'] }}&from=stock"
          class="block rounded-2xl bg-white border border-slate-200 px-4 py-3 shadow-sm hover:bg-slate-50 transition-colors">

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
          </div>
        </a>
      @endforeach
    </div>
  @endif
</div>
