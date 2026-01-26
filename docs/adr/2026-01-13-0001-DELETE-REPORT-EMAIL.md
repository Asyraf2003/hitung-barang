# ADR 0001: Penghapusan Fitur Laporan Email (Gmail) dan Tabel Dispatch

Tanggal: 2026-01-13  
Status: Accepted

## Context (Keadaan awal)
- Project memiliki fitur pengiriman laporan periodik ke email (Gmail), termasuk halaman “dispatches” untuk melihat histori pengiriman.
- Komponen mencakup Command/Service pengirim email, blade email template, model `ReportDispatch`, route/menu `/app/dispatches`, dan tabel database `report_dispatches`.
- Konfigurasi SMTP di `.env` sudah tidak digunakan, namun artefak fitur masih ada di codebase dan database.

## Problem (Masalah)
- Fitur laporan email tidak akan dipakai lagi, menjadi *dead code* yang memperbesar beban maintenance.
- Adanya risiko error runtime dari referensi class/file yang sudah tidak relevan.
- Adanya data “hantu” di database (tabel tetap ada tanpa fungsi).
- Target: Hapus bersih fitur report-email tanpa mengganggu fitur inti lainnya.

## Options (Opsi)
1) Nonaktifkan saja (hapus konfigurasi, biarkan kode dan DB tetap ada).
2) Hapus bersih total (kode + route/menu/controller + template + tabel DB).

## Decision (Keputusan)
Memilih **Opsi 2**: Menghapus total fitur report-email.
Tindakan yang dilakukan:
- Menghapus file implementasi: Command, Service, Model, dan blade email template.
- Menghapus akses UI: route constraint, menu link, dan controller yang menggunakan `ReportDispatch`.
- Menghapus copywriting terkait “laporan otomatis ke Gmail”.
- Menghapus tabel database `report_dispatches` secara eksplisit melalui migration.

## Consequences (Dampak)
Positif:
- Codebase lebih bersih dan ringan (mengurangi surface area maintenance).
- Menghilangkan risiko error referensi ke komponen yang sudah dihapus.
- Database bersih dari tabel yang tidak lagi memiliki fungsi.
- Fokus fitur laporan dialihkan sepenuhnya ke export PDF.

Negatif/Risiko:
- Histori dispatch email hilang permanen.
- Jika fitur dibutuhkan kembali di masa depan, harus dibangun ulang dari awal.
