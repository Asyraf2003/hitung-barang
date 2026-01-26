# ADR 0006: Kebijakan Edit & Pembatalan Transaksi (Void + Correct) untuk Modul Riwayat dan Laporan Stok

Tanggal: 2026-01-19  
Status: Accepted

## Context (Keadaan awal)
- Modul Inventory menyimpan setiap perubahan stok sebagai **InventoryMovement** (IN / OUT / ADJUST).
- Awalnya sistem memperbolehkan **update langsung (`UPDATE qty_kg`)** pada record transaksi.
- Modul Laporan (UI & PDF) melakukan agregasi berdasarkan `inventory_movements` untuk periode tertentu.
- Ditemukan kasus:
  - Edit transaksi menyebabkan **angka laporan membengkak** (double count).
  - Riwayat menjadi ambigu karena histori transaksi “berubah” tanpa jejak audit.
- Secara domain, sistem ini **lebih dekat ke ledger / e-wallet**, bukan CRUD biasa.

## Problem (Masalah)
- **Audit Integrity Rusak:** Mengubah transaksi lama menghapus jejak historis nilai awal.
- **Laporan Tidak Deterministik:** Update langsung menyebabkan laporan periode lama berubah, bahkan bisa menambah kuantitas secara tidak logis.
- **UX Ambigu:** User tidak tahu apakah transaksi “asli” atau hasil edit.
- **Domain Drift:** Edit langsung bertentangan dengan prinsip immutable ledger.

## Options (Opsi)
1) **Hard Edit (UPDATE langsung)**  
   - Mudah secara teknis.  
   - ❌ Merusak audit, laporan, dan histori.

2) **Disable Edit (Delete Only)**  
   - Aman secara audit.  
   - ❌ Tidak realistis secara operasional (human error pasti ada).

3) **Void + Correct (Dipilih)**  
   - Transaksi lama dibatalkan (voided).  
   - Transaksi baru dibuat sebagai koreksi.  
   - Relasi eksplisit antar transaksi.

## Decision (Keputusan)
Memilih **Opsi 3: Void + Correct Pattern**.

### Aturan Domain
- **Transaksi tidak pernah diubah secara destruktif.**
- Edit transaksi =  
  1. Tandai transaksi lama sebagai **voided**.  
  2. Buat transaksi baru dengan nilai koreksi.
- Struktur metadata:
  - `meta.voided_at` → timestamp pembatalan
  - `meta.corrects_id` → ID transaksi yang dikoreksi

### Dampak ke Modul
- **Riwayat (History UI):**
  - Default: hanya tampilkan transaksi aktif (non-voided).
  - Transaksi hasil edit diberi badge **“Koreksi”**.
  - Transaksi voided:
    - Tidak bisa diedit / dihapus ulang.
    - Opsional ditampilkan via filter **“Tampilkan dibatalkan”**.
    - Tampilan redup + badge **“Dibatalkan”**.

- **Laporan (UI & PDF):**
  - Hanya menghitung transaksi **non-voided**.
  - Laporan periode historis **tidak berubah** meski ada koreksi di masa depan.
  - Agregasi selalu konsisten dan idempotent.

- **Saldo Stok:**
  - Void + correct tetap menjaga keseimbangan stok karena:
    - Void tidak mengubah saldo langsung.
    - Koreksi direkam sebagai movement baru yang sah.

## Consequences (Dampak)

### Positif
- Audit trail kuat dan eksplisit.
- Laporan stabil, tidak “berubah masa lalu”.
- Domain aligned dengan sistem keuangan/ledger.
- UX jelas: user tahu mana transaksi asli vs koreksi.

### Negatif / Trade-off
- Implementasi lebih kompleks (metadata + filter).
- Query laporan wajib memfilter `voided_at IS NULL`.
- Developer harus disiplin, tidak pakai `UPDATE` sembarangan.

## Mitigasi
- **Larangan eksplisit:** `UPDATE inventory_movements.qty_kg` untuk edit user flow.
- Helper/domain service wajib dipakai (`updateMovement` → void + create).
- Semua query agregasi:
  - Wajib `JSON_EXTRACT(meta,'$.voided_at') IS NULL`.
- UI memberi affordance visual (badge Koreksi/Dibatalkan).
- Tambahkan test kasus:
  - Edit transaksi periode lalu.
  - Pastikan laporan lama tidak berubah.

## Catatan Domain
- Tipe `ADJUST` **bukan edit**, melainkan:
  - Koreksi stok hasil opname / audit fisik.
- Edit transaksi ≠ ADJUST.
- ADJUST tetap dianggap movement sah dan immutable.
