<!doctype html>
<html lang="id">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <title>{{ $title }}</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; line-height: 1.4; }
    h1 { font-size: 16px; margin: 0 0 4px 0; text-transform: uppercase; }
    .meta { color: #475569; font-size: 10px; margin-bottom: 15px; }
    .pill { display: inline-block; padding: 2px 8px; border-radius: 999px; background: #e6f4ff; color: #118EEA; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #e2e8f0; padding: 6px 8px; vertical-align: middle; }
    th { background: #f8fafc; text-align: left; color: #64748b; text-transform: uppercase; font-size: 9px; }
    .right { text-align: right; }
    .totals-container { width: 100%; margin-top: 20px; border-top: 2px solid #f1f5f9; padding-top: 10px; }
    .totals-table { width: 320px; float: right; }
    .totals-table td { border: none; padding: 3px 0; font-size: 11px; }
    .text-bold { font-weight: bold; }
  </style>
</head>
<body>
  @php
    $rawMode = strtoupper(trim(str_ireplace('Laporan', '', (string)($title ?? ''))));
    $map = ['DAILY' => 'Harian', 'WEEKLY' => 'Mingguan', 'MONTHLY' => 'Bulanan'];
    $modeIndo = $map[$rawMode] ?? 'Periode';
  @endphp

  <h1>{{ $title }}</h1>
  <div class="meta">
    <span class="pill">{{ $period_text }}</span>
    &nbsp;•&nbsp; Dibuat: {{ $generated_at }}
  </div>

  <table>
    <thead>
      <tr>
        <th>Barang</th>
        <th>Tipe Barang</th>
        <th class="right">Masuk (kg)</th>
        <th class="right">Keluar (kg)</th>
        <th class="right">Stok Akhir Toko (kg)</th>
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $r)
        <tr>
          <td>{{ $r['barang'] ?? '-' }}</td>
          <td>{{ $r['tipe'] ?? '-' }}</td>
          <td class="right">{{ number_format((float)($r['in_kg'] ?? 0), 2, ',', '.') }}</td>
          <td class="right">{{ number_format((float)($r['out_kg'] ?? 0), 2, ',', '.') }}</td>
          <td class="right">{{ number_format((float)($r['balance_end_kg'] ?? 0), 2, ',', '.') }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="5" style="text-align: center; color: #94a3b8;">Tidak ada data transaksi pada periode ini.</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <div class="totals-container">
    <table class="totals-table">
      <tr>
        <td>Total Masuk ({{ $modeIndo }})</td>
        <td class="right text-bold">{{ number_format((float)($totals['in_kg'] ?? 0), 2, ',', '.') }} kg</td>
      </tr>
      <tr>
        <td>Total Keluar ({{ $modeIndo }})</td>
        <td class="right text-bold">{{ number_format((float)($totals['out_kg'] ?? 0), 2, ',', '.') }} kg</td>
      </tr>
      <tr style="color: #118EEA;">
        <td class="text-bold">Total Stok Berat Di Toko</td>
        <td class="right text-bold">{{ number_format((float)($totals['balance_end_kg'] ?? 0), 2, ',', '.') }} kg</td>
      </tr>
    </table>
  </div>
</body>
</html>
