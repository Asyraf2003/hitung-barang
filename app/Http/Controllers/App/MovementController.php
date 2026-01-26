<?php

namespace App\Http\Controllers\App;

use App\Domain\Inventory\InventoryService;
use App\Http\Controllers\Controller;
use App\Models\ItemType;
use Illuminate\Http\Request;
use RuntimeException;

final class MovementController extends Controller
{
    public function store(Request $request, InventoryService $svc)
    {
        // Normalize qty_kg input
        $raw = (string) $request->input('qty_kg', '');
        $raw = trim(str_replace(' ', '', $raw));

        // "1.234,50" => "1234.50"
        if (str_contains($raw, ',') && str_contains($raw, '.')) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } elseif (str_contains($raw, ',') && !str_contains($raw, '.')) {
            $raw = str_replace(',', '.', $raw);
        }

        $request->merge(['qty_kg' => $raw]);

        $data = $request->validate([
            'type' => ['required', 'in:IN,OUT'],

            'item_id' => ['required', 'integer', 'min:1'],

            'use_new_type' => ['nullable', 'in:1'],
            'item_type_id' => ['nullable', 'integer', 'min:1'],

            // diameter hanya dipakai saat use_new_type=1 (khusus wire)
            'diameter_mm' => ['nullable', 'string', 'max:20', 'regex:/^\d+([.,]\d{1,2})?$/'],

            // qty dalam kg, max 2 desimal
            'qty_kg' => ['required', 'string', 'max:20', 'regex:/^\d+(\.\d{1,2})?$/'],

            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $useNew = ($data['use_new_type'] ?? null) === '1';
        $itemId = (int) $data['item_id'];

        // Resolve item type
        if ($useNew) {
            $diam = trim((string) ($data['diameter_mm'] ?? ''));
            if ($diam === '') {
                return back()->withInput()->withErrors(['diameter_mm' => 'Tipe (diameter) wajib diisi']);
            }
            $type = $svc->ensureWireType($itemId, $diam);
        } else {
            $id = (int) ($data['item_type_id'] ?? 0);
            if ($id <= 0) {
                return back()->withInput()->withErrors(['item_type_id' => 'Pilih tipe barang']);
            }
            $type = ItemType::query()->whereKey($id)->where('item_id', $itemId)->firstOrFail();
        }

        $qty = round((float) $data['qty_kg'], 2);
        if ($qty <= 0) {
            return back()->withInput()->withErrors(['qty_kg' => 'Jumlah harus > 0']);
        }

        try {
            if ($data['type'] === 'IN') {
                $svc->recordIn($type->id, $qty, now(), $data['note'] ?? null);
            } else {
                $svc->recordOut($type->id, $qty, now(), $data['note'] ?? null);
            }

        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['qty_kg' => $e->getMessage()]);
        }

        return redirect('/app/history?item_type_id=' . $type->id)->with('toast', 'Tersimpan');
    }

    public function update(Request $request, $id, InventoryService $svc)
    {
        // 1) Normalisasi qty_kg (seragam dengan store)
        $request->merge([
            'qty_kg' => $this->normalizeQtyKg((string) $request->input('qty_kg', '')),
        ]);

        // 2) Validasi
        $data = $request->validate([
            'qty_kg' => ['required', 'string', 'max:20', 'regex:/^\d+(\.\d{1,2})?$/'],
            'note'   => ['nullable', 'string', 'max:2000'],
        ]);

        // 3) Guard (seragam dengan store)
        $qty = round((float) $data['qty_kg'], 2);
        if ($qty <= 0) {
            return response()->json(['ok' => false, 'message' => 'Jumlah harus > 0'], 422);
        }

        // 4) Eksekusi
        try {
            $svc->updateMovement((int) $id, $qty, $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'message' => 'Tersimpan']);
    }

    public function destroy($id, InventoryService $svc)
    {
        // 1) Eksekusi (modelnya memang gak butuh validasi input)
        try {
            $svc->deleteMovement((int) $id);
        } catch (RuntimeException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'message' => 'Terhapus']);
    }

    /**
     * Normalisasi angka input (mendukung "1.234,50" / "1234,50" / "1234.50").
     * Output selalu format "1234.50" atau "1234".
     */
    private function normalizeQtyKg(string $raw): string
    {
        $raw = trim(str_replace(' ', '', $raw));

        if (str_contains($raw, ',') && str_contains($raw, '.')) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } elseif (str_contains($raw, ',') && !str_contains($raw, '.')) {
            $raw = str_replace(',', '.', $raw);
        }

        return $raw;
    }

}
