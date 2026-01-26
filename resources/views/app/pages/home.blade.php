@php
  $summary = $summary ?? ['total_balance_kg'=>0,'today_in_kg'=>0,'today_out_kg'=>0];
  $charts  = $charts ?? [
    'inOut' => ['labels'=>[],'in'=>[],'out'=>[]],
  ];

  $mode = $mode ?? 'daily';
  $base_date = $base_date ?? now('Asia/Makassar')->toDateString();
  $rangeLabel = $range_label ?? ($mode === 'daily' ? '10 hari' : ($mode === 'weekly' ? '10 minggu' : '10 bulan'));

  $filters = $filters ?? ['item_id'=>'','item_type_id'=>''];
  $nav = $nav ?? ['prev' => '/app/home?mode='.$mode, 'next' => '/app/home?mode='.$mode];

  $item_options = $item_options ?? [];
  $type_options = $type_options ?? [];

  $qsBase = ['date' => $base_date];
  if (!empty($filters['item_type_id'])) $qsBase['item_type_id'] = $filters['item_type_id'];
  elseif (!empty($filters['item_id'])) $qsBase['item_id'] = $filters['item_id'];
@endphp

<script type="application/json" id="homeChartData">@json($charts)</script>

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
      <a data-pjax href="{{ $nav['prev'] ?? '#' }}"
         class="rounded-xl border border-slate-200 px-3 py-2 text-center text-slate-700">
        ‹ Sebelumnya
      </a>
      <a data-pjax href="{{ $nav['next'] ?? '#' }}"
         class="rounded-xl border border-slate-200 px-3 py-2 text-center text-slate-700">
        Selanjutnya ›
      </a>
    </div>
  </div>
</div>
