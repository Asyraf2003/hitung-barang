<?php

namespace App\Domain\Inventory;

use App\Models\InventoryMovement;
use App\Models\ItemType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

final class InventoryService
{
    /**
     * Pastikan tipe barang ada (khusus wire: input diameter mm).
     */
    public function ensureWireType(int $itemId, string $diameterMm): ItemType
    {
        $key = $this->normalizeDiameter($diameterMm);

        return ItemType::query()->firstOrCreate(
            ['item_id' => $itemId, 'type_key' => $key],
            ['label' => "{$key} mm", 'diameter_mm' => $key]
        );
    }

    /**
     * Saldo stok (kg) untuk 1 item_type. Bisa "per tanggal" pakai $at (<= at).
     */
    public function balanceKg(int $itemTypeId, ?Carbon $at = null): float
    {
        $base = InventoryMovement::query()
            ->where('item_type_id', $itemTypeId);

        if ($at) {
            $base->where('occurred_at', '<=', $at);
        }

        $inAdj = (float) (clone $base)->whereIn('type', [InventoryMovement::TYPE_IN, InventoryMovement::TYPE_ADJUST])->sum('qty_kg');
        $out   = (float) (clone $base)->where('type', InventoryMovement::TYPE_OUT)->sum('qty_kg');

        return round($inAdj - $out, 2);
    }

    public function recordIn(
        int $itemTypeId,
        float $qtyKg,
        ?Carbon $occurredAt = null,
        ?string $note = null,
        array $meta = []
    ): InventoryMovement {
        return $this->recordMovement($itemTypeId, InventoryMovement::TYPE_IN, $qtyKg, $occurredAt, $note, $meta);
    }

    public function recordOut(
        int $itemTypeId,
        float $qtyKg,
        ?Carbon $occurredAt = null,
        ?string $note = null,
        array $meta = []
    ): InventoryMovement {
        return $this->recordMovement($itemTypeId, InventoryMovement::TYPE_OUT, $qtyKg, $occurredAt, $note, $meta);
    }

    public function recordMovement(
        int $itemTypeId,
        string $type,
        float $qtyKg,
        ?Carbon $occurredAt = null,
        ?string $note = null,
        array $meta = []
    ): InventoryMovement {
        if (!in_array($type, [InventoryMovement::TYPE_IN, InventoryMovement::TYPE_OUT, InventoryMovement::TYPE_ADJUST], true)) {
            throw new InvalidArgumentException('type tidak valid');
        }
        if ($qtyKg <= 0) {
            throw new InvalidArgumentException('qty_kg harus > 0');
        }

        $occurredAt ??= now();

        return DB::transaction(function () use ($itemTypeId, $type, $qtyKg, $occurredAt, $note, $meta) {
            /** @var ItemType $t */
            $t = ItemType::query()->whereKey($itemTypeId)->lockForUpdate()->firstOrFail();

            $current = (float) $t->balance_kg;
            $delta = (float) $qtyKg;

            // IN dan ADJUST dianggap menambah saldo
            if ($type === InventoryMovement::TYPE_IN || $type === InventoryMovement::TYPE_ADJUST) {
                $newBal = $current + $delta;
            } else { // OUT
                if ($current < $delta) {
                    throw new RuntimeException("Stok tidak cukup. Saldo {$current} kg, diminta {$delta} kg.");
                }
                $newBal = $current - $delta;
            }

            // update saldo tersimpan
            $t->balance_kg = number_format(round($newBal, 2), 2, '.', '');
            $t->save();

            // insert movement (audit trail)
            return InventoryMovement::query()->create([
                'item_type_id' => $itemTypeId,
                'type' => $type,
                'qty_kg' => number_format(round($delta, 2), 2, '.', ''),
                'occurred_at' => $occurredAt,
                'note' => $note,
                'meta' => $meta ?: null,
            ]);
        });
    }

    private function normalizeDiameter(string $diameterMm): string
    {
        $diameterMm = trim(str_replace([' ', ','], ['', '.'], $diameterMm));

        if ($diameterMm === '' || !is_numeric($diameterMm)) {
            throw new InvalidArgumentException('diameter_mm harus numeric');
        }

        return number_format((float) $diameterMm, 2, '.', '');
    }

    public function deleteMovement(int $movementId): void
    {
        DB::transaction(function () use ($movementId) {
            $m = InventoryMovement::query()->lockForUpdate()->findOrFail($movementId);
            
            /** @var ItemType $t */
            $t = ItemType::query()->whereKey($m->item_type_id)->lockForUpdate()->firstOrFail();

            $currentBal = (float) $t->balance_kg;
            $qty = (float) $m->qty_kg;

            // Balikkan saldo (Reversal)
            if ($m->type === InventoryMovement::TYPE_IN || $m->type === InventoryMovement::TYPE_ADJUST) {
                // Jika tadinya masuk, maka sekarang dikurangi
                $newBal = $currentBal - $qty;
            } else {
                // Jika tadinya keluar, maka sekarang ditambah
                $newBal = $currentBal + $qty;
            }

            // Proteksi: Saldo tidak boleh negatif setelah dihapus
            if ($newBal < 0) {
                throw new RuntimeException("Gagal menghapus: Saldo akhir tidak boleh negatif ({$newBal} kg).");
            }

            // Update saldo tipe barang
            $t->balance_kg = number_format(round($newBal, 2), 2, '.', '');
            $t->save();

            // Hapus record movement
            $m->delete();
        });
    }

    public function updateMovement(int $movementId, float $newQty, ?string $newNote = null): void
    {
        if ($newQty <= 0) {
            throw new InvalidArgumentException('qty_kg harus > 0');
        }

        DB::transaction(function () use ($movementId, $newQty, $newNote) {
            /** @var InventoryMovement $m */
            $m = InventoryMovement::query()->lockForUpdate()->findOrFail($movementId);

            $meta = $m->meta ?? [];
            if (!empty($meta['voided_at'])) {
                throw new RuntimeException('Transaksi ini sudah dikoreksi dan tidak bisa diedit lagi.');
            }

            /** @var ItemType $t */
            $t = ItemType::query()->whereKey($m->item_type_id)->lockForUpdate()->firstOrFail();

            $oldQty = (float) $m->qty_kg;
            $newQty = round((float) $newQty, 2);

            $currentBal = (float) $t->balance_kg;
            $diff = $newQty - $oldQty;

            // Impact saldo berdasarkan tipe movement (sama seperti logic kamu sekarang)
            if ($m->type === InventoryMovement::TYPE_IN || $m->type === InventoryMovement::TYPE_ADJUST) {
                $newBal = $currentBal + $diff;
            } else { // OUT
                $newBal = $currentBal - $diff;
            }

            if ($newBal < 0) {
                throw new RuntimeException("Gagal koreksi: Saldo akhir tidak boleh negatif ({$newBal} kg).");
            }

            // 1) Update saldo tersimpan
            $t->balance_kg = number_format(round($newBal, 2), 2, '.', '');
            $t->save();

            // 2) Void movement lama (audit tetap ada, tapi tidak dihitung bisnis)
            $m->meta = array_merge($meta, [
                'voided_at' => now()->toISOString(),
                'void_reason' => 'corrected',
            ]);
            $m->save();

            // 3) Insert movement pengganti (occurred_at tetap)
            InventoryMovement::query()->create([
                'item_type_id' => $m->item_type_id,
                'type' => $m->type,
                'qty_kg' => number_format($newQty, 2, '.', ''),
                'occurred_at' => $m->occurred_at,
                'note' => $newNote,
                'meta' => [
                    'corrects_id' => $m->id,
                ],
            ]);
        });
    }
}
