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

        <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="text-sm text-slate-500">Barang</div>
              <div class="text-lg font-semibold truncate">{{ $it['barang'] ?? '-' }}</div>
              <div class="text-sm text-black font-semibold mt-2 truncate">
                {{ $it['tipe'] ?? '-' }}
              </div>
            </div>

            <div class="text-right shrink-0">
              <div class="text-sm text-slate-500">Sisa</div>
              <div class="text-lg font-semibold">
                {{ number_format($balanceKg, 2, ',', '.') }} kg
              </div>
            </div>
          </div>

          <div class="mt-3 grid grid-cols-2 gap-2">
            <a data-pjax
              href="/app/input?type=IN&item_type_id={{ $it['item_type_id'] }}&item_id={{ $it['item_id'] }}"
              class="rounded-xl bg-[#118EEA]/10 text-[#118EEA] border border-[#118EEA]/20 py-2 text-center text-sm font-semibold active:scale-95 transition-transform">
              + Masuk
            </a>

            <a data-pjax
              href="{{ $canOut ? ('/app/input?type=OUT&item_type_id='.$it['item_type_id'].'&item_id='.$it['item_id']) : '#' }}"
              aria-disabled="{{ $canOut ? 'false' : 'true' }}"
              tabindex="{{ $canOut ? '0' : '-1' }}"
              class="rounded-xl py-2 text-center text-sm font-semibold transition-transform
                      {{ $canOut
                        ? 'bg-rose-50 text-rose-700 border border-rose-200 active:scale-95'
                        : 'bg-slate-100 text-slate-400 border border-slate-200 pointer-events-none'
                      }}">
              - Keluar
            </a>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>
