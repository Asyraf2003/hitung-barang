# ADR 0005: Normalisasi Zona Waktu UI (WITA) dan Konsistensi Query UTC untuk Modul Riwayat, Laporan, dan PDF

Tanggal: 2026-01-17  
Status: Accepted

## Context (Keadaan awal)
- Aplikasi menyimpan `occurred_at` (`inventory_movements`) dalam basis waktu server/DB (UTC).
- `config('app.timezone')` bernilai UTC, sedangkan user menggunakan aplikasi di zona Asia/Makassar (WITA).
- Modul Home, History, Reports, dan PDF melakukan filtering periode berdasarkan tanggal lokal namun menampilkan data dari database.

## Problem (Masalah)
- **Inkonsistensi Data:** Terjadi perbedaan tanggal antar halaman (misal: History mencatat tanggal 16, Home mencatat tanggal 17) karena konversi zona waktu tidak seragam.
- **Boundary Error:** Filter tanggal pada query database menggunakan input WITA langsung tanpa konversi ke UTC, menyebabkan transaksi antara jam 00:00–07:59 WITA berpotensi tidak terjaring (hilang dari laporan).
- **UX Confusion:** Periode pada PDF dan waktu pembuatan laporan perlu mengikuti zona waktu user agar tidak membingungkan.

## Options (Opsi)
1) **UTC End-to-End:** Semua display dan query menggunakan UTC. (UX Buruk).
2) **WITA End-to-End:** Mengubah timezone database/server ke WITA. (Bukan best practice, berisiko pada migrasi infra).
3) **Hybrid (Dipilih):** Penyimpanan tetap UTC, namun boundary periode dibangun di WITA lalu dikonversi ke UTC untuk query. Display selalu WITA.

## Decision (Keputusan)
- Memilih **Opsi 3 (Hybrid)**:
    - Storage & Database tetap **UTC**.
    - Fitur berbasis tanggal di UI menggunakan **Asia/Makassar (WITA)**.
    - Logic Query: Bangun `$startLocal` & `$endLocal` (WITA) -> Konversi ke `$startUtc` & `$endUtc` -> Query ke DB.
    - Tampilan: Semua timestamp dikonversi ke WITA saat rendering di UI maupun PDF.
    - PDF menampilkan `period_text` dan `generated_at` dalam zona WITA.

## Consequences (Dampak)
Positif:
- Konsistensi tanggal/jam di seluruh modul (Home/History/Reports/PDF).
- Akurasi filter: Tidak ada transaksi yang "hilang" di batas pergantian hari.
- Audit aman: Storage UTC stabil lintas environment.

Negatif/Risiko:
- Risiko regresi jika developer membuat query baru tanpa konversi zona waktu.
- Potensi kebingungan bagi developer baru mengenai dualisme waktu (UI vs Storage).

## Mitigasi
- Terapkan pola baku: Local boundary (WITA) → Convert UTC → Query.
- Verifikasi boundary dengan data ekstrem (transaksi jam 00:10 WITA).
- Dokumentasi komentar pada setiap blok kode yang membangun window waktu.
