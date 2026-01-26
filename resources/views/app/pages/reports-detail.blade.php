@php
  $item_type = $item_type ?? ['id'=>0,'barang'=>'-','tipe'=>'-'];
  $mode = $mode ?? 'daily';
  $base_date = $base_date ?? now()->toDateString();
@endphp

<div class="space-y-3">
  <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
    <a data-pjax href="/app/reports?mode={{ $mode }}&date={{ $base_date }}"
       class="text-xs font-semibold text-[#118EEA]">‹ Kembali</a>

    <div class="mt-2 text-base font-semibold">
      Detail {{ $item_type['barang'] }} • {{ $item_type['tipe'] }}
    </div>
    <div class="text-sm text-slate-500">
      Periode: {{ $period_label ?? $base_date }}
    </div>

    <div class="mt-3 grid grid-cols-3 gap-2 text-center">
      <div class="rounded-xl bg-[#118EEA]/10 border border-[#118EEA]/20 p-2">
        <div class="text-[11px] text-slate-600">Masuk</div>
        <div class="text-sm font-semibold text-[#118EEA]">
          {{ number_format((float)($totals['in_kg'] ?? 0), 2, ',', '.') }} kg
        </div>
      </div>
      <div class="rounded-xl bg-rose-50 border border-rose-200 p-2">
        <div class="text-[11px] text-slate-600">Keluar</div>
        <div class="text-sm font-semibold text-rose-700">
          {{ number_format((float)($totals['out_kg'] ?? 0), 2, ',', '.') }} kg
        </div>
      </div>
      <div class="rounded-xl bg-white border border-slate-200 p-2">
        <div class="text-[11px] text-slate-600">Sisa (akhir)</div>
        <div class="text-sm font-semibold">
          {{ number_format((float)($totals['balance_end_kg'] ?? 0), 2, ',', '.') }} kg
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
