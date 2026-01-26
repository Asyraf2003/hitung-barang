# ADR 0002: Penghapusan Fitur Email Dispatch dan Pembaruan Laporan PDF Periodik

Tanggal: 2026-01-13  
Status: Accepted

## Context (Keadaan awal)
- Proyek memiliki fitur pengiriman laporan otomatis melalui email menggunakan komponen `ReportSender`, `SendReportEmail` (Command), dan model `ReportDispatch`.
- Laporan PDF periodik sudah ada namun hanya menampilkan ringkasan total tanpa detail rincian barang.
- Tabel database `report_dispatches` digunakan untuk mencatat log pengiriman email.

## Problem (Masalah)
- Fitur pengiriman email sudah tidak diperlukan dan harus dihapus untuk simplifikasi kode.
- Laporan PDF kurang informatif karena tidak menampilkan rincian transaksi per diameter kawat (stok masuk, keluar, dan sisa per item).
- Label periode pada PDF masih menggunakan bahasa Inggris.

## Options (Opsi)
1) Mempertahankan kode lama dan hanya menyembunyikan UI.
2) Penghapusan total (Cleanup) fitur email dan perombakan template PDF menjadi laporan detail berbasis data riil.

## Decision (Keputusan)
- Memilih **Opsi 2**:
    - Menghapus semua file dan logic terkait `ReportDispatch`.
    - Melakukan sinkronisasi data pada `ReportPdfController` untuk menyertakan variabel `$rows` dan `balance_end_g`.
    - Mengubah template `report-period.blade.php` menjadi tabel detail dengan mapping Bahasa Indonesia (Harian, Mingguan, Bulanan).

## Consequences (Dampak)
Positif:
- Struktur kode bersih (Clean Code) dan performa laporan PDF tetap ringan.
- Data laporan akurat dan sinkron antara total kesimpulan dengan rincian per item.
- Laporan siap cetak dengan format bahasa yang sesuai kebutuhan user.

Negatif/Risiko:
- Data historis log email pada tabel `report_dispatches` tidak dapat dipulihkan setelah migration dijalankan.
