@php
  $mode = $mode ?? 'daily';
  $base_date = $base_date ?? ($date ?? now()->toDateString());
  $period_label = $period_label ?? ($date ?? $base_date);
@endphp

<div class="space-y-3">
  <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
    <div class="flex items-start justify-between gap-3">
      <div>
        <div class="text-base font-semibold">Laporan</div>
        <div class="mt-1 text-sm text-slate-500">
          {{ $mode === 'daily' ? 'Harian' : ($mode === 'weekly' ? 'Mingguan' : 'Bulanan') }}
          • {{ $period_label }}
        </div>
      </div>

      <div class="flex items-center gap-2 overflow-x-auto pb-1">
        <a href="{{ route('app.reports.pdf', ['mode' => $mode, 'date' => $base_date]) }}"
          class="shrink-0 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700">
          PDF
        </a>
      </div>
    </div>

    <div class="mt-4 grid grid-cols-3 gap-2 text-xs font-semibold">
      <a data-pjax href="/app/reports?mode=daily&date={{ $base_date }}"
         class="rounded-xl border px-3 py-2 text-center {{ $mode==='daily' ? 'border-[#118EEA] bg-[#118EEA]/10 text-[#118EEA]' : 'border-slate-200 text-slate-600' }}">
        Harian
      </a>
      <a data-pjax href="/app/reports?mode=weekly&date={{ $base_date }}"
         class="rounded-xl border px-3 py-2 text-center {{ $mode==='weekly' ? 'border-[#118EEA] bg-[#118EEA]/10 text-[#118EEA]' : 'border-slate-200 text-slate-600' }}">
        Mingguan
      </a>
      <a data-pjax href="/app/reports?mode=monthly&date={{ $base_date }}"
         class="rounded-xl border px-3 py-2 text-center {{ $mode==='monthly' ? 'border-[#118EEA] bg-[#118EEA]/10 text-[#118EEA]' : 'border-slate-200 text-slate-600' }}">
        Bulanan
      </a>
    </div>

    <div class="mt-3 flex items-center justify-between gap-2">
      <a data-pjax href="/app/reports?mode={{ $mode }}&date={{ $prev_date }}"
         class="rounded-xl bg-[#118EEA]/10 text-[#118EEA] px-3 py-2 text-xs font-semibold">
        ‹ {{ $mode==='daily' ? 'Kemarin' : ($mode==='weekly' ? 'Minggu lalu' : 'Bulan lalu') }}
      </a>

      <div class="text-sm font-semibold text-slate-700">
        {{ $period_label }}
      </div>

      <a data-pjax href="/app/reports?mode={{ $mode }}&date={{ $next_date }}"
         class="rounded-xl bg-[#118EEA]/10 text-[#118EEA] px-3 py-2 text-xs font-semibold">
        {{ $mode==='daily' ? 'Besok' : ($mode==='weekly' ? 'Minggu depan' : 'Bulan depan') }} ›
      </a>
    </div>

    <div class="mt-3 grid grid-cols-2 gap-3">
      <div class="rounded-2xl bg-[#118EEA]/10 border border-[#118EEA]/20 p-3">
        <div class="text-xs text-slate-600">Total Masuk</div>
        <div class="mt-1 text-lg font-semibold text-[#118EEA]">
          {{ number_format((float)($totals['in_kg'] ?? 0), 2, ',', '.') }} kg
        </div>
      </div>
      <div class="rounded-2xl bg-rose-50 border border-rose-200 p-3">
        <div class="text-xs text-slate-600">Total Keluar</div>
        <div class="mt-1 text-lg font-semibold text-rose-700">
          {{ number_format((float)($totals['out_kg'] ?? 0), 2, ',', '.') }} kg
        </div>
      </div>
    </div>
  </div>

  @if(empty($rows) || count($rows) === 0)
    <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm text-sm text-slate-500">
      Belum ada data untuk periode ini.
    </div>
  @else
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b border-slate-200 text-sm font-semibold">
        Per Tipe Barang
      </div>

      <div class="divide-y divide-slate-100">
        @foreach($rows as $r)
          <a data-pjax
            href="/app/reports-detail?mode={{ $mode }}&date={{ $base_date }}&item_type_id={{ $r['id'] }}"
            class="block px-4 py-3 hover:bg-slate-50 transition-colors">
            <div class="flex items-center justify-between gap-3">
              <div class="min-w-0">
                <div class="text-sm font-semibold truncate">{{ $r['barang'] }} • {{ $r['tipe'] }}</div>
                <div class="mt-1 text-[11px] text-slate-400">Tap untuk detail</div>
              </div>

              <div class="text-right shrink-0">
                <div class="text-xs text-slate-500">
                  +{{ number_format((float)($r['in_kg'] ?? 0), 2, ',', '.') }} kg ·
                  -{{ number_format((float)($r['out_kg'] ?? 0), 2, ',', '.') }} kg
                </div>

                <div class="mt-1">
                  <div class="text-sm text-slate-500">Sisa</div>
                  <div class="text-lg font-semibold">
                    {{ number_format((float)($r['balance_end_kg'] ?? 0), 2, ',', '.') }} kg
                  </div>
                </div>
              </div>
            </div>
          </a>
        @endforeach
      </div>
    </div>
  @endif
</div>
