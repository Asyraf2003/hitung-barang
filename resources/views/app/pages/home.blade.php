@php
  use Carbon\Carbon;

  $summary = $summary ?? ['total_balance_kg'=>0,'today_in_kg'=>0,'today_out_kg'=>0];
  $charts  = $charts ?? ['inOut' => ['labels'=>[],'in'=>[],'out'=>[]]];

  $mode = $mode ?? 'daily';
  $tz = 'Asia/Makassar';

  // RANGE FIX: 5
  $span = 5;

  // Anchor date biar weekly/monthly gak “lompat rasa”
  $rawBase = $base_date ?? now($tz)->toDateString();
  $anchor = Carbon::parse($rawBase, $tz);

  if ($mode === 'weekly') $anchor = $anchor->startOfWeek(Carbon::MONDAY);
  elseif ($mode === 'monthly') $anchor = $anchor->startOfMonth();
  else $anchor = $anchor->startOfDay();

  $base_date = $anchor->toDateString();
  $rangeLabel = $mode === 'daily' ? "{$span} hari" : ($mode === 'weekly' ? "{$span} minggu" : "{$span} bulan");

  $filters = $filters ?? ['item_id'=>'','item_type_id'=>''];
  $item_options = $item_options ?? [];
  $type_options = $type_options ?? [];

  $qsBase = ['date' => $base_date];
  if (!empty($filters['item_type_id'])) $qsBase['item_type_id'] = $filters['item_type_id'];
  elseif (!empty($filters['item_id'])) $qsBase['item_id'] = $filters['item_id'];

  // prev/next geser 5 unit
  if ($mode === 'weekly') {
    $prevDate = $anchor->copy()->subWeeks($span)->toDateString();
    $nextDate = $anchor->copy()->addWeeks($span)->toDateString();
  } elseif ($mode === 'monthly') {
    $prevDate = $anchor->copy()->subMonthsNoOverflow($span)->toDateString();
    $nextDate = $anchor->copy()->addMonthsNoOverflow($span)->toDateString();
  } else {
    $prevDate = $anchor->copy()->subDays($span)->toDateString();
    $nextDate = $anchor->copy()->addDays($span)->toDateString();
  }

  $prevHref = '/app/home?' . http_build_query(array_merge($qsBase, ['mode' => $mode, 'date' => $prevDate]));
  $nextHref = '/app/home?' . http_build_query(array_merge($qsBase, ['mode' => $mode, 'date' => $nextDate]));

  // disable next kalau sudah lewat hari ini (biar chart gak “kosong lalu auto-scale aneh”)
  $today = now($tz)->startOfDay();
  $nextDisabled = Carbon::parse($nextDate, $tz)->gt($today);
@endphp

<script
  type="application/json"
  id="homeChartData"
  data-base-date="{{ $base_date }}"
  data-mode="{{ $mode }}"
>@json($charts)</script>

<div class="space-y-3">
  <div class="grid grid-cols-2 gap-3">
    <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
      <div class="text-xs text-slate-500">Total Stok</div>
      <div class="mt-1 text-lg font-semibold">{{ number_format((float)($summary['total_balance_kg'] ?? 0), 2, ',', '.') }} kg</div>
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
      <div class="text-xs text-slate-500">Keluar Hari Ini</div>
      <div class="mt-1 text-lg font-semibold">{{ number_format((float)($summary['today_out_kg'] ?? 0), 2, ',', '.') }} kg</div>
    </div>
  </div>

  {{-- Filter --}}
  <div class="rounded-2xl bg-white border border-slate-200 p-3 shadow-sm">
    <div class="grid grid-cols-2 gap-2">
      <div>
        <div class="text-xs text-slate-500">Barang</div>
        <select id="homeFilterItem" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
          <option value="">Semua</option>
          @foreach ($item_options as $o)
            <option value="{{ $o['id'] }}" {{ (string)($filters['item_id'] ?? '') === (string)$o['id'] ? 'selected' : '' }}>
              {{ $o['name'] }}
            </option>
          @endforeach
        </select>
      </div>

      <div>
        <div class="text-xs text-slate-500">Tipe</div>
        <select id="homeFilterType" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
          <option value="">Semua</option>
          @foreach ($type_options as $o)
            <option value="{{ $o['id'] }}" {{ (string)($filters['item_type_id'] ?? '') === (string)$o['id'] ? 'selected' : '' }}>
              {{ $o['label'] }}
            </option>
          @endforeach
        </select>
      </div>
    </div>

    <a
      data-pjax
      id="homeApplyFilterLink"
      data-mode="{{ $mode }}"
      data-date="{{ $base_date }}"
      class="hidden"
      href="/app/home?{{ http_build_query(array_merge($qsBase, ['mode' => $mode])) }}"
    ></a>
  </div>

  {{-- Toggle mode --}}
  <div class="rounded-2xl bg-white border border-slate-200 p-3 shadow-sm">
    <div class="grid grid-cols-3 gap-2 text-xs font-semibold">
      <a data-pjax href="/app/home?{{ http_build_query(array_merge($qsBase, ['mode' => 'daily'])) }}"
         class="rounded-xl border px-3 py-2 text-center {{ $mode==='daily' ? 'border-[#118EEA] bg-[#118EEA]/10 text-[#118EEA]' : 'border-slate-200 text-slate-600' }}">
        Harian
      </a>
      <a data-pjax href="/app/home?{{ http_build_query(array_merge($qsBase, ['mode' => 'weekly'])) }}"
         class="rounded-xl border px-3 py-2 text-center {{ $mode==='weekly' ? 'border-[#118EEA] bg-[#118EEA]/10 text-[#118EEA]' : 'border-slate-200 text-slate-600' }}">
        Mingguan
      </a>
      <a data-pjax href="/app/home?{{ http_build_query(array_merge($qsBase, ['mode' => 'monthly'])) }}"
         class="rounded-xl border px-3 py-2 text-center {{ $mode==='monthly' ? 'border-[#118EEA] bg-[#118EEA]/10 text-[#118EEA]' : 'border-slate-200 text-slate-600' }}">
        Bulanan
      </a>
    </div>
  </div>

  <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
    <div class="flex items-center justify-between">
      <div class="text-sm font-semibold">Data Masuk Dan Keluar ({{ $rangeLabel }})</div>
      <div class="text-xs text-slate-500">{{ $base_date }}</div>
    </div>

    <div class="mt-3 h-56">
      <canvas id="chartInOut"></canvas>
    </div>

    <div class="mt-3 grid grid-cols-2 gap-2 text-xs font-semibold">
      <a data-pjax href="{{ $prevHref }}"
        class="rounded-xl border border-slate-200 px-3 py-2 text-center text-slate-700">
        ‹ Sebelumnya
      </a>

      <a data-pjax
        href="{{ $nextDisabled ? '#' : $nextHref }}"
        aria-disabled="{{ $nextDisabled ? 'true' : 'false' }}"
        tabindex="{{ $nextDisabled ? '-1' : '0' }}"
        class="rounded-xl border border-slate-200 px-3 py-2 text-center
          {{ $nextDisabled ? 'text-slate-400 bg-slate-50 pointer-events-none' : 'text-slate-700' }}">
        Selanjutnya ›
      </a>
    </div>
  </div>
</div>
