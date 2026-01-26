# ADR-000X: Migrasi Stok ke Model Generic (kg) + Dashboard Home (Chart, Filter, Windowing)
Tanggal: 2026-01-14
Status: Accepted

## Konteks
Sistem awal punya jejak “wire-centric” (kawat) dan satuan gram (`*_g`, `balance_g`, dll). Di saat yang sama, domain yang dibutuhkan lebih general: bisa banyak barang, banyak tipe per barang, dan seluruh stok/movement konsisten dalam kg.

Masalah yang muncul selama migrasi:
- Skema/fitur lama campur aduk (wire_items, gram) vs skema baru (items, item_types, inventory_movements, kg).
- Home page butuh dashboard:
  - Grafik batang IN vs OUT untuk: 10 hari terakhir / 10 minggu terakhir / 10 bulan terakhir.
  - Bisa “geser window” (prev/next 10 bucket).
  - Bisa filter per barang dan per tipe barang (bukan total keseluruhan).
  - UI punya toggle mode yang harus konsisten “aktif”.
- Aplikasi pakai PJAX, jadi inisialisasi chart dan filter harus re-init saat PJAX load.
- Waktu UI di Asia/Makassar, sementara app config masih UTC dan DB store timestamp sebagai UTC (praktisnya begitu).

## Keputusan
1) **Migrasi model data stok menjadi generic dan konsisten dalam kg**
- Gunakan tabel:
  - `items` (master barang)
  - `item_types` (varian/tipe per barang: misal diameter kawat)
  - `inventory_movements` (ledger movement: IN/OUT/ADJUST, semua `qty_kg`)
- Semua perhitungan stok berbasis ledger `inventory_movements`:
  - Balance = SUM(IN + ADJUST) - SUM(OUT)
- `InventoryService` menjadi sumber kebenaran domain untuk:
  - ensure tipe (khusus wire: normalize diameter)
  - record movement
  - hitung saldo (opsional per tanggal)

2) **Dashboard Home: agregasi server-side 10 bucket + window navigation**
- Home menerima query:
  - `mode`: daily|weekly|monthly
  - `date`: anchor date untuk window
  - `item_id` atau `item_type_id` untuk filter
- Home menghasilkan data untuk view:
  - `summary`:
    - `total_balance_kg`
    - `today_in_kg`
    - `today_out_kg`
  - `charts`:
    - `inOut.labels` (10 label)
    - `inOut.in` (10 value)
    - `inOut.out` (10 value)
  - `filters`:
    - `item_id`
    - `item_type_id`
  - `item_options`, `type_options`
  - `nav.prev`, `nav.next` (URL untuk geser window 10 bucket)

3) **Timezone handling**
- UI timezone ditetapkan `Asia/Makassar` untuk bucket/label.
- Query DB memakai rentang UTC:
  - hitung `$startLocal/$endLocal` di UI TZ
  - convert ke UTC untuk `whereBetween(occurred_at, ...)`
- Bucket assignment:
  - `occurred_at` dikonversi ke UI TZ sebelum menentukan key bucket (day/week/month).

4) **PJAX + Chart.js init**
- `resources/js/app/charts.js` bertanggung jawab:
  - destroy chart lama sebelum init ulang
  - parse JSON dari `<script id="homeChartData">`
  - init chart bar (Chart.js)
  - init filter dropdown (onchange -> build URL -> click link PJAX)
- Warna dataset diatur eksplisit (masuk biru, keluar merah) di config Chart.js.

5) **Mode toggle “aktif” ditentukan dari data controller**
- View home membutuhkan `$mode` real dari controller.
- Controller home wajib set:
  - `$data['mode'] = $mode;`
Agar Blade tidak fallback ke default `daily` dan membuat highlight toggle salah.

## Alternatif yang Dipertimbangkan
1) Agregasi client-side (ambil raw movements banyak lalu bucket di JS)
- Ditolak: berat di browser, banyak data, PJAX reinit lebih rawan.

2) Buat endpoint API terpisah untuk chart (AJAX fetch)
- Ditunda: tambahan endpoint, auth, caching, lebih kompleks dari kebutuhan saat ini.

3) Simpan saldo harian/mingguan/bulanan (materialized summary table)
- Ditolak sementara: premature optimization. Ledger sudah cukup untuk skala saat ini.

## Detail Implementasi
### Skema Database
- `items(code, name, unit='kg', meta)`
- `item_types(item_id, type_key, label, diameter_mm?, meta)`
- `inventory_movements(item_type_id, type, qty_kg, occurred_at, note, meta)`
Index:
- `inventory_movements(type)`
- `(item_type_id, occurred_at)`

### Domain Service
- `InventoryService::balanceKg(itemTypeId, at?)` untuk saldo per tipe.
- `recordOut` transaksi + lock `ItemType` untuk menghindari race.

### Controller: Home (di AppController::page)
- Validasi `mode`.
- Tentukan `anchor` dari `date`.
- Tentukan filter:
  - `item_type_id` (paling spesifik)
  - fallback `item_id` -> resolve semua type id milik item
- Hitung window 10 bucket:
  - daily: anchor day end, start = anchor start - 9 hari
  - weekly: anchor endOfWeek, start = startOfWeek - 9 minggu
  - monthly: anchor endOfMonth, start = startOfMonth - 9 bulan
- Agregasi:
  - ambil movements dalam window (UTC)
  - bucket di UI TZ
  - sum IN dan OUT per bucket
- Summary:
  - total balance (filter scope)
  - today in/out (UI day boundaries -> UTC range)

### View: Home
- Menampilkan summary (kg).
- Filter dropdown:
  - barang (item)
  - tipe (item_type)
- Toggle mode:
  - link berisi `date + filter + mode`
  - class aktif berdasarkan `$mode`
- Chart canvas:
  - data dari `homeChartData` JSON

### JS: charts.js
- init saat DOMContentLoaded dan `pjax:loaded`.
- `initHomeFilters`:
  - `item onchange` -> reset type -> apply
  - `type onchange` -> apply
  - apply = set URLSearchParams + trigger click hidden `a[data-pjax]`
- Chart.js bar:
  - dataset Masuk (kg) dan Keluar (kg)
  - beginAtZero
  - warna biru/merah ditetapkan di dataset

## Konsekuensi
Positif:
- Model data stok jadi konsisten dan reusable lintas barang.
- Home dashboard cepat karena agregasi 10 bucket di server.
- Window navigation memudahkan melihat periode lama tanpa memuat grafik raksasa.
- Filter per barang/tipe jelas dan query scope terkontrol.

Negatif / Tradeoff:
- Perhitungan balance total masih SUM ledger, biaya query meningkat seiring data.
- Timezone complexity: harus disiplin konversi UI TZ <-> UTC.
- Filter UI bergantung pada PJAX click trigger, rawan kalau event handler tidak ter-bind saat PJAX edge case.

## Risiko dan Mitigasi
1) Risiko: Toggle mode tidak aktif karena `$mode` tidak dikirim ke view
- Mitigasi: pastikan controller set `$data['mode'] = $mode;` di blok home.

2) Risiko: Salah bucket karena timezone
- Mitigasi: semua key bucket ditentukan setelah `occurred_at` dikonversi ke UI TZ.

3) Risiko: Query ledger makin berat saat data membesar
- Mitigasi:
  - index `(item_type_id, occurred_at)`
  - pertimbangkan materialized summary table jika volume sudah besar.

4) Risiko: Filter dropdown tidak merespon pada PJAX state tertentu
- Mitigasi: init ulang di event `pjax:loaded`, pastikan selector root benar.

## Cara Verifikasi
1) Home mode
- Buka:
  - `/app/home?mode=daily`
  - `/app/home?mode=weekly`
  - `/app/home?mode=monthly`
- Pastikan toggle aktif sesuai mode.

2) Window navigation
- Klik “Sebelumnya/Selanjutnya” dan pastikan label dan data berubah sesuai 10 bucket.

3) Filter scope
- Pilih Barang -> data chart dan summary berubah.
- Pilih Tipe -> data makin spesifik.
- Pastikan param di URL benar:
  - `item_id` atau `item_type_id` (bukan keduanya sekaligus).

4) Konsistensi unit
- Semua tampilan home memakai `kg`.
- DB tetap menyimpan `qty_kg`.

## DoD
- Skema `items/item_types/inventory_movements` aktif dan dipakai oleh flow input/history/reports.
- Home dashboard:
  - 10 bucket daily/weekly/monthly
  - prev/next window bekerja
  - filter barang dan tipe bekerja
  - toggle mode highlight benar
  - chart ter-render ulang saat PJAX navigasi
- Tidak ada referensi `*_g` untuk home dashboard.

## Catatan
- Config `app.timezone` masih `UTC`. UI TZ untuk bucketing diputuskan eksplisit di home logic (`Asia/Makassar`).
- Jika ke depan semua UI harus WITA, pertimbangkan set `config/app.php` timezone ke `Asia/Makassar` secara global dan audit semua date parsing.
