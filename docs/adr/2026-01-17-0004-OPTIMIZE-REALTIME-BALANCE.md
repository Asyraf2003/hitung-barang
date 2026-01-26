# ADR 0004: Optimalisasi Saldo Real-time dan Proteksi Anti-Spam pada Input Transaksi

Tanggal: 2026-01-17  
Status: Accepted

## Context (Keadaan awal)
- Aplikasi menggunakan halaman Input (Masuk/Keluar) berbasis PJAX dengan komponen JS (input-balance.js, diameter-picker.js).
- Saldo dihitung real-time melalui endpoint /app/api/balance yang memanggil InventoryService::balanceKg().
- Fungsi balanceKg() melakukan agregasi SUM() dari seluruh histori tabel inventory_movements setiap kali dipanggil.

## Problem (Masalah)
- **Performa Buruk:** Proses agregasi SUM() dilakukan 2x per request. Interaksi user yang cepat memicu pemanggilan berulang yang membebani database dan membuat UI terasa berat.
- **Stabilitas UI:** Terjadi rekursi event pada diameter-picker.js yang memicu error Maximum call stack size exceeded.
- **Risiko Spam & Race Condition:** Belum ada proteksi terhadap interaksi cepat (spam klik) yang berpotensi menghasilkan data ganda atau validasi saldo OUT yang tidak akurat pada kondisi concurrency.

## Options (Opsi)
1) **Compute-on-read:** Tetap menggunakan agregasi histori dengan tambahan optimasi indeks database dan debounce pada frontend.
2) **Store-and-update (Denormalisasi):** Menambahkan kolom saldo permanen pada tabel master barang yang diupdate secara atomik.
3) **Hybrid (Dipilih):** Menggunakan saldo tersimpan untuk kebutuhan UI/Real-time dan tetap mempertahankan histori untuk audit laporan periodik.

## Decision (Keputusan)
- Memilih **Opsi 3 (Hybrid)** dengan implementasi:
    - Menambahkan kolom balance_kg (decimal(14,2)) pada tabel item_types.
    - Mengubah logic InventoryService: Menggunakan lockForUpdate() pada row item_types, validasi saldo via kolom baru, dan update saldo secara atomik dalam DB::transaction() sebelum mencatat ke inventory_movements.
    - Optimasi API: Endpoint /app/api/balance kini hanya membaca kolom balance_kg tanpa kalkulasi SUM().
    - Perbaikan JS: Menghapus rekursi event pada diameter-picker.js dan menerapkan debounce pada input diameter untuk mengurangi beban request.
    - Sinkronisasi Seeder: Memastikan migrate:fresh --seed menghitung saldo awal balance_kg agar sesuai dengan data histori dummy.

## Consequences (Dampak)
Positif:
- Performa pengecekan saldo meningkat drastis.
- Error Maximum call stack size exceeded teratasi dan UI lebih responsif.
- Konsistensi data terjaga melalui database locking, mencegah saldo negatif.
- Audit trail tetap utuh di tabel movements.

Negatif/Risiko:
- Potensi data drift (selisih saldo) jika ada manipulasi database di luar service.
- Mitigasi: Sediakan mekanisme rekalkulasi saldo dari histori (manual/periodik) untuk validasi.
- Peningkatan kompleksitas kode karena harus menjaga sinkronisasi data.
