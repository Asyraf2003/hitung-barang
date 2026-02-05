# ADR 0007: Standarisasi Ringkasan Periode (Span=5) & Navigasi Periode untuk Beranda dan Laporan (Mobile-First)

Tanggal: 2026-02-05  
Status: Accepted

## Context (Keadaan awal)
- Aplikasi inventory dibuat **mobile-first** (Laravel dipakai sebagai web-app untuk perangkat mobile).
- Beranda menampilkan ringkasan grafik **Masuk/Keluar** dengan window default **10 titik** (hari/minggu/bulan tergantung mode).
- Navigasi periode menggunakan tombol **Sebelumnya/Selanjutnya**, namun perpindahan periode mengikuti logika window lama (10), sehingga:
  - Grafik menjadi **penuh** dan kurang terbaca di layar kecil.
  - Perpindahan periode terasa **“lompat”** dan membingungkan.
- Weekly/monthly sempat dinormalisasi ke **startOfWeek/startOfMonth**, yang membuat user merasa “bukan hari ini” saat membuka mode tersebut.

## Problem (Masalah)
- **Visual Density Tinggi:** 10 bar pada chart + label tanggal membuat tampilan penuh dan sulit discan cepat.
- **Navigasi Tidak Intuitif:** Tombol sebelumnya/selanjutnya berpindah terlalu jauh sehingga user merasa ada data yang “terlewat”.
- **Inkonsistensi UI vs Data:** Label rentang (mis. “10 hari”) bisa tidak sesuai dengan apa yang ditampilkan (mis. UI menampilkan 5 bar).
- **Confusing Anchor Date:** Weekly/monthly yang otomatis jatuh ke awal minggu/bulan menimbulkan persepsi “beranda tidak menunjukkan hari ini”.

## Options (Opsi)
1) **Tetap Span=10 (status quo)**
   - ✅ Tidak perlu perubahan data/backend.
   - ❌ Grafik terlalu penuh di mobile, navigasi terasa lompat.

2) **Span=5 untuk chart saja, nav tetap 10**
   - ✅ Chart lebih lega.
   - ❌ Navigasi tetap lompat; UX masih terasa tidak konsisten.

3) **Span=5 untuk chart + nav geser 5 (Dipilih)**
   - ✅ Grafik lebih jelas.
   - ✅ Navigasi lebih “nyambung” dan mudah dipahami.
   - ✅ Label rentang dapat dibuat konsisten.

4) **Anchor weekly/monthly ke startOfWeek/startOfMonth**
   - ✅ Secara definisi periode lebih “rapi”.
   - ❌ Bertentangan dengan ekspektasi user: “mode weekly/monthly default harus hari ini”.

## Decision (Keputusan)
Memilih **Opsi 3** + kebijakan anchor date yang user-friendly:

### Aturan UI/UX
- **Span ringkasan ditetapkan = 5** untuk tampilan grafik (daily/weekly/monthly).
- Tombol **Sebelumnya/Selanjutnya** menggeser periode sebesar **5 unit** sesuai mode:
  - daily: ±5 hari  
  - weekly: ±5 minggu  
  - monthly: ±5 bulan
- **Default base_date untuk semua mode = “hari ini”** (bukan startOfWeek/startOfMonth).
- Grafik menampilkan **maks 5 titik** (dipotong di frontend), dengan urutan kronologis yang stabil.

### Aturan Implementasi
- **Home Blade**:
  - `span = 5`
  - `rangeLabel` mengikuti span (5 hari/minggu/bulan)
  - `prevHref/nextHref` dihitung dari `base_date` dengan ±span unit.
- **charts.js**:
  - `normalizeSeries(data.inOut, 5)` untuk:
    - menyamakan panjang array labels/in/out
    - sorting kronologis
    - mengambil 5 titik terakhir

## Consequences (Dampak)

### Positif
- Grafik lebih **terbaca** di layar mobile.
- Navigasi periode terasa **konsisten dan nyambung**.
- Label periode tidak lagi misleading (UI selaras dengan yang ditampilkan).
- Default weekly/monthly sesuai ekspektasi user: “lihat kondisi **hari ini**”.

### Negatif / Trade-off
- Ringkasan menampilkan konteks historis lebih sedikit per layar (5 vs 10).
- Jika user butuh konteks lebih panjang, harus lewat:
  - halaman detail laporan, atau
  - PDF/export.

## Mitigasi
- Pastikan backend/controller yang menyiapkan `$charts` tetap aman walau mengirim >5 titik (frontend akan memotong).
- Tambahkan guard/tes:
  - Navigasi daily harus bergeser tepat ±5 hari.
  - Weekly/monthly tetap mempertahankan `base_date` = hari ini secara default.
  - Grafik selalu maksimal 5 titik, tidak zig-zag.
- Jika di masa depan dibutuhkan rentang lebih panjang:
  - sediakan “mode ringkasan” (mis. 5/10/30) sebagai preferensi, bukan default.

## Catatan Domain
- ADR ini **murni UX layer** (ringkasan & navigasi), tidak mengubah aturan ledger.
- Kebijakan ADR 0006 tetap berlaku:
  - Query laporan/agregasi wajib mengabaikan transaksi voided (`meta.voided_at` tidak null).
  - Edit transaksi tetap melalui pola **Void + Correct** (immutable).
