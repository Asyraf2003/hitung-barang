<?php

namespace App\Http\Controllers\App;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;
use App\Domain\Inventory\InventoryService;
use App\Http\Controllers\Controller;
use App\Models\ItemType;
use App\Models\Item;

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

            'use_new_item' => ['nullable', 'in:1'],
            'item_id' => ['required_without:use_new_item', 'nullable', 'integer', 'min:1'],
            'item_name' => ['required_if:use_new_item,1', 'nullable', 'string', 'max:100'],
            'item_code' => ['nullable', 'string', 'max:32', 'regex:/^[A-Za-z0-9_]+$/'],

            'use_new_type' => ['nullable', 'in:1'],
            'item_type_id' => ['nullable', 'integer', 'min:1'],

            'diameter_mm' => ['nullable', 'string', 'max:20', 'regex:/^\d+([.,]\d{1,2})?$/'],
            'qty_kg' => ['required', 'string', 'max:20', 'regex:/^\d+(\.\d{1,2})?$/'],

            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $useNewItem = ($data['use_new_item'] ?? null) === '1';
        $useNewType = ($data['use_new_type'] ?? null) === '1';

        if ($useNewItem) {
            $name = trim((string) ($data['item_name'] ?? ''));
            if ($name === '') {
                return back()->withInput()->withErrors(['item_name' => 'Nama barang wajib diisi']);
            }

            $codeRaw = strtoupper(trim((string) ($data['item_code'] ?? '')));

            if ($codeRaw !== '') {
                $code = preg_replace('/[^A-Z0-9_]/', '', $codeRaw);
                $code = substr($code, 0, 32);

                if ($code === '') {
                    return back()->withInput()->withErrors(['item_code' => 'Kode tidak valid']);
                }
                if (Item::query()->where('code', $code)->exists()) {
                    return back()->withInput()->withErrors(['item_code' => 'Kode sudah dipakai']);
                }
            } else {
                $base = Str::upper(Str::snake(Str::ascii($name)));
                $base = preg_replace('/[^A-Z0-9_]/', '', $base);
                $base = trim($base, '_');
                if ($base === '') $base = 'ITEM';

                $code = substr($base, 0, 32);
                $i = 2;
                while (Item::query()->where('code', $code)->exists()) {
                    $suffix = '_' . $i;
                    $maxBaseLen = 32 - strlen($suffix);
                    $code = substr($base, 0, max(1, $maxBaseLen)) . $suffix;
                    $i++;
                }
            }

            $item = Item::query()->create([
                'code' => $code,
                'name' => $name,
                'unit' => 'kg',
                'meta' => null,
            ]);

            $itemId = (int) $item->id;

            // Barang baru wajib bikin type juga (karena movement butuh item_type_id)
            if (!$useNewType) {
                return back()->withInput()->withErrors(['use_new_type' => 'Untuk barang baru, tipe wajib dibuat juga']);
            }
        } else {
            $itemId = (int) ($data['item_id'] ?? 0);
        }

        $useNew = $useNewType;

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
