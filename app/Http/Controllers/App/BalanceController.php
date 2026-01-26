<?php

namespace App\Http\Controllers\App;

use App\Domain\Inventory\InventoryService;
use App\Http\Controllers\Controller;
use App\Models\ItemType;
use Illuminate\Http\Request;

final class BalanceController extends Controller
{
    public function show(Request $request, InventoryService $svc)
    {
        // 1) Prefer item_type_id
        $typeId = (int) $request->query('item_type_id', 0);
        if ($typeId > 0) {
            $t = ItemType::query()->find($typeId);

            if (!$t) {
                return response()->json([
                    'ok' => false,
                    'item_type_id' => null,
                    'balance_kg' => 0.00,
                    'message' => 'item_type_id not found',
                ], 404);
            }

            return response()->json([
                'ok' => true,
                'item_type_id' => (int) $t->id,
                'balance_kg' => round((float) $t->balance_kg, 2),
            ]);
        }

        // 2) Fallback: item_id + diameter_mm (buat tambah tipe kawat)
        $itemId = (int) $request->query('item_id', 0);
        $diam = (string) $request->query('diameter_mm', '');
        $diam = trim(str_replace(',', '.', $diam));

        if ($itemId <= 0) {
            return response()->json([
                'ok' => false,
                'item_type_id' => null,
                'balance_kg' => 0.00,
                'message' => 'item_id required',
            ], 422);
        }

        if ($diam === '' || !preg_match('/^\d+(\.\d{1,2})?$/', $diam)) {
            return response()->json([
                'ok' => false,
                'item_type_id' => null,
                'balance_kg' => 0.00,
                'message' => 'diameter_mm invalid',
            ], 422);
        }

        $typeKey = number_format((float) $diam, 2, '.', '');
        $t = ItemType::query()
            ->where('item_id', $itemId)
            ->where('type_key', $typeKey)
            ->first();

        if (!$t) {
            return response()->json([
                'ok' => true,
                'item_type_id' => null,
                'balance_kg' => 0.00,
            ]);
        }

        return response()->json([
            'ok' => true,
            'item_type_id' => (int) $t->id,
            'balance_kg' => round((float) $t->balance_kg, 2),
        ]);
    }
}
