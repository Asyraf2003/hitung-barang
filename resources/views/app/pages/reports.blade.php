@php
  $mode = $mode ?? 'daily';
  $base_date = $base_date ?? ($date ?? now()->toDateString());
  $period_label = $period_label ?? ($date ?? $base_date);
@endphp

<div class="space-y-3">
  <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
    <div class="flex items-center justify-between gap-3">
      <div class="min-w-0">
        <div class="text-base font-semibold text-slate-800 leading-tight">Laporan</div>
        <div class="mt-0.5 text-sm text-slate-500 leading-tight">
          {{ $mode === 'daily' ? 'Harian' : ($mode === 'weekly' ? 'Mingguan' : 'Bulanan') }}
          • {{ $period_label }}
        </div>
      </div>

      <div class="shrink-0">
        <a href="{{ route('app.reports.pdf', ['mode' => $mode, 'date' => $base_date]) }}"
          class="flex items-center gap-2 rounded-full bg-[#118EEA] px-4 py-2 shadow-sm active:scale-95 transition-all">
          
          <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
          </svg>
          
          <span class="text-[11px] font-bold text-white uppercase tracking-wide">Cetak</span>
        </a>
      </div>
    </div>

    {{-- Filter Mode --}}
    <div class="mt-4 grid grid-cols-3 gap-2 text-xs font-semibold">
      <a data-pjax href="/app/reports?mode=daily&date={{ $base_date }}"
         class="rounded-2xl border px-3 py-2 text-center transition-colors {{ $mode==='daily' ? 'border-[#118EEA] bg-[#118EEA]/10 text-[#118EEA]' : 'border-slate-200 text-slate-600' }}">
        Harian
      </a>
      <a data-pjax href="/app/reports?mode=weekly&date={{ $base_date }}"
         class="rounded-2xl border px-3 py-2 text-center transition-colors {{ $mode==='weekly' ? 'border-[#118EEA] bg-[#118EEA]/10 text-[#118EEA]' : 'border-slate-200 text-slate-600' }}">
        Mingguan
      </a>
      <a data-pjax href="/app/reports?mode=monthly&date={{ $base_date }}"
         class="rounded-2xl border px-3 py-2 text-center transition-colors {{ $mode==='monthly' ? 'border-[#118EEA] bg-[#118EEA]/10 text-[#118EEA]' : 'border-slate-200 text-slate-600' }}">
        Bulanan
      </a>
    </div>

    {{-- Navigasi Periode --}}
    <div class="mt-3 flex items-center justify-between gap-2">
      <a data-pjax href="/app/reports?mode={{ $mode }}&date={{ $prev_date }}"
         class="rounded-2xl bg-[#118EEA]/10 text-[#118EEA] px-3 py-2 text-xs font-semibold active:scale-95 transition-transform">
        ‹ {{ $mode==='daily' ? 'Kemarin' : ($mode==='weekly' ? 'Minggu lalu' : 'Bulan lalu') }}
      </a>

      <div class="text-sm font-semibold text-slate-700">
        {{ $period_label }}
      </div>

      <a data-pjax href="/app/reports?mode={{ $mode }}&date={{ $next_date }}"
         class="rounded-2xl bg-[#118EEA]/10 text-[#118EEA] px-3 py-2 text-xs font-semibold active:scale-95 transition-transform">
        {{ $mode==='daily' ? 'Besok' : ($mode==='weekly' ? 'Minggu depan' : 'Bulan depan') }} ›
      </a>
    </div>

    @php
      $in = (float)($totals['in_kg'] ?? 0);
      $out = (float)($totals['out_kg'] ?? 0);
      $net = $in - $out;
    @endphp

    {{-- Ringkasan Total --}}
    <div class="mt-3 grid grid-cols-3 gap-2">
      <div class="rounded-2xl bg-[#118EEA]/10 border border-[#118EEA]/20 p-2.5 flex flex-col items-center justify-center text-center">
        <div class="text-[10px] text-slate-500 leading-tight uppercase tracking-wide">Masuk</div>
        <div class="mt-1 text-sm font-bold text-[#118EEA] leading-none truncate w-full">
          {{ number_format($in, 2, ',', '.') }}
        </div>
      </div>

      <div class="rounded-2xl bg-rose-50 border border-rose-200 p-2.5 flex flex-col items-center justify-center text-center">
        <div class="text-[10px] text-slate-500 leading-tight uppercase tracking-wide">Keluar</div>
        <div class="mt-1 text-sm font-bold text-rose-700 leading-none truncate w-full">
          {{ number_format($out, 2, ',', '.') }}
        </div>
      </div>

      <div class="rounded-2xl border p-2.5 flex flex-col items-center justify-center text-center {{ $net >= 0 ? 'bg-emerald-50 border-emerald-200' : 'bg-rose-50 border-rose-200' }}">
        <div class="text-[10px] text-slate-500 leading-tight uppercase tracking-wide">Net</div>
        <div class="mt-1 text-sm font-bold leading-none truncate w-full {{ $net >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
          {{ ($net >= 0 ? '+' : '') . number_format($net, 2, ',', '.') }}
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
          @php
            $in = (float)($r['in_kg'] ?? 0);
            $out = (float)($r['out_kg'] ?? 0);
            $net = $in - $out;
          @endphp

          <a data-pjax
            href="/app/reports-detail?mode={{ $mode }}&date={{ $base_date }}&item_type_id={{ $r['id'] }}"
            class="block px-4 py-3 hover:bg-slate-50 transition-colors">
            <div class="flex items-center justify-between gap-3">
              <div class="min-w-0">
                <div class="text-sm font-semibold truncate">{{ $r['barang'] }} • {{ $r['tipe'] }}</div>

                <div class="mt-2 flex flex-wrap gap-1.5">
                  <span class="inline-flex items-center rounded-full border border-[#118EEA]/20 bg-[#118EEA]/10 px-2.5 py-1 text-[11px] font-semibold text-[#118EEA]">
                    +{{ number_format($in, 2, ',', '.') }} kg
                  </span>

                  <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-[11px] font-semibold text-rose-700">
                    -{{ number_format($out, 2, ',', '.') }} kg
                  </span>

                  <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold
                    {{ $net >= 0 ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700' }}">
                    Net {{ $net >= 0 ? '+' : '' }}{{ number_format($net, 2, ',', '.') }} kg
                  </span>
                </div>

                <div class="mt-2 text-[11px] text-slate-400">Lihat detail</div>
              </div>

              <div class="text-right shrink-0">
                <div class="text-xs text-slate-500">Sisa</div>
                <div class="text-lg font-semibold">
                  {{ number_format((float)($r['balance_end_kg'] ?? 0), 2, ',', '.') }} kg
                </div>
              </div>
            </div>
          </a>
        @endforeach
      </div>
    </div>
  @endif
</div>
