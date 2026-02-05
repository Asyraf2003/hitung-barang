# ADR 0008: Relokasi Aksi Masuk/Keluar dari List Stok ke Halaman Detail (Mis-tap Safe, Mobile-First)

Tanggal: 2026-02-05  
Status: Accepted

## Context (Keadaan awal)
- Halaman **Stok** menampilkan daftar item dengan informasi sisa stok.
- Setiap baris item memiliki tombol aksi cepat **Masuk (+)** dan **Keluar (−)**.
- Aplikasi bersifat **mobile-first** dan digunakan langsung di lapangan.
- Pada perangkat mobile:
  - Area tap sempit.
  - Scroll sering bercampur dengan aksi.

## Problem (Masalah)
- **Mis-tap Risiko Tinggi:**  
  Tombol **Keluar (−)** dapat terpencet tidak sengaja saat scroll.
- **Aksi Tanpa Konteks:**  
  User bisa melakukan transaksi tanpa melihat detail item terlebih dahulu.
- **UI Terlalu Action-Heavy:**  
  Halaman Stok seharusnya bersifat **observational** (cek sisa), bukan transactional.
- **Kesalahan Operasional Mahal:**  
  Salah input stok lebih berbahaya daripada 1 tap tambahan.

## Decision (Keputusan)
Melakukan **relokasi aksi Masuk/Keluar dari List Stok ke Halaman Detail Item**.

### Aturan UI/UX
- **Halaman Stok**
  - Setiap baris item **clickable** menuju halaman Detail.
  - Tombol **Masuk (+)** dan **Keluar (−)** dihapus dari list.
  - Fokus halaman: *melihat stok*, bukan melakukan aksi.

- **Halaman Detail Item**
  - Menampilkan informasi lengkap item & tipe.
  - Menyediakan CTA eksplisit:
    - **Tambah Stok** → `/app/input?type=IN&item_type_id=...&item_id=...`
    - **Kurangi Stok** → `/app/input?type=OUT&item_type_id=...&item_id=...`
      - Disabled jika saldo = 0.
  - CTA memiliki visual affordance yang jelas dan jarak aman untuk tap.

- **Navigasi**
  - Tombol **Kembali** di halaman Detail mengikuti konteks asal:
    - Dari Stok → kembali ke Stok.
    - Dari Laporan → kembali ke Laporan.

## Consequences (Dampak)

### Positif
- ✅ Risiko salah input stok berkurang drastis.
- ✅ User dipaksa melihat konteks sebelum bertindak.
- ✅ Halaman Stok lebih bersih dan mudah discan.
- ✅ UX lebih aman untuk penggunaan mobile.

### Negatif / Trade-off
- ❌ Penambahan **1 tap ekstra** untuk aksi Masuk/Keluar.
- Trade-off ini **disengaja** demi keselamatan data stok.

## Implementation Notes
- **Stok Blade**
  - Card/baris item dibungkus `<a>` menuju halaman Detail.
  - Hapus tombol +/− dari list.

- **Detail Blade**
  - Tambahkan CTA buttons Masuk/Keluar.
  - Terapkan disable state untuk Keluar jika stok = 0.
  - Implementasikan `backHref` berbasis referer / context param.

## Catatan Domain
- Keputusan ini selaras dengan prinsip:
  - *Read-first, act-second* untuk sistem inventori.
- Mendukung ADR sebelumnya:
  - **ADR 0006 (Void + Correct):** mengurangi potensi transaksi tidak disengaja.
  - **ADR 0007 (Span=5 UX):** mobile-first, keterbacaan > kepadatan fitur.
