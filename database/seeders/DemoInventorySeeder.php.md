<?php

namespace Database\Seeders;

use App\Models\InventoryMovement;
use App\Models\Item;
use App\Models\ItemType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoInventorySeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            $this->command?->warn('DemoInventorySeeder dilewati (bukan local/testing).');
            return;
        }

        Schema::disableForeignKeyConstraints();
        DB::table('inventory_movements')->truncate();
        DB::table('item_types')->truncate();
        DB::table('items')->truncate();
        Schema::enableForeignKeyConstraints();

        $tz = config('app.timezone') ?: 'Asia/Makassar';

        $start = now($tz)->subMonths(5)->startOfMonth();
        $end   = now($tz)->subDay()->endOfDay();

        // Barang: Kawat
        $wireItem = Item::query()->create([
            'code' => 'WIRE',
            'name' => 'Kawat',
            'unit' => 'kg',
            'meta' => null,
        ]);

        // Tipe: diameter 0.10 .. 1.00 step 0.05
        $typeIds = [];
        for ($i = 10; $i <= 100; $i += 5) {
            $d = number_format($i / 100, 2, '.', ''); // "0.10" .. "1.00"
            $t = ItemType::query()->create([
                'item_id' => $wireItem->id,
                'type_key' => $d,
                'label' => "{$d} mm",
                'diameter_mm' => $d,
                'meta' => null,
            ]);
            $typeIds[] = $t->id;
        }

        // In-memory balance (kg) biar cepat dan tidak minus
        $balance = array_fill_keys($typeIds, 0.0);

        $period = CarbonPeriod::create($start->copy()->startOfDay(), '1 day', $end->copy()->startOfDay());

        foreach ($period as $day) {
            $day = Carbon::parse($day->toDateString(), $tz);

            $isQuietDay = random_int(1, 100) <= 15;
            if ($isQuietDay) continue;

            // IN: 0–3 /hari, 1.00–2.00 kg
            $inCount = random_int(0, 3);
            for ($i = 0; $i < $inCount; $i++) {
                $typeId = $typeIds[array_rand($typeIds)];
                $qty = random_int(100, 200) / 100; // 1.00 .. 2.00

                $at = $day->copy()->setTime(random_int(8, 12), random_int(0, 59), random_int(0, 59));

                InventoryMovement::query()->create([
                    'item_type_id' => $typeId,
                    'type' => InventoryMovement::TYPE_IN,
                    'qty_kg' => number_format($qty, 2, '.', ''),
                    'occurred_at' => $at,
                    'note' => 'Restock',
                    'meta' => null,
                ]);

                $balance[$typeId] += $qty;
            }

            // OUT: 5–25 /hari, 0.03–0.30 kg
            $outCount = random_int(5, 25);
            for ($j = 0; $j < $outCount; $j++) {
                $typeId = $typeIds[array_rand($typeIds)];
                $qty = random_int(3, 30) / 100; // 0.03 .. 0.30

                if ($balance[$typeId] < $qty) {
                    $doRestock = random_int(1, 100) <= 60;
                    if ($doRestock) {
                        $restock = random_int(100, 200) / 100;

                        $atIn = $day->copy()->setTime(random_int(8, 12), random_int(0, 59), random_int(0, 59));

                        InventoryMovement::query()->create([
                            'item_type_id' => $typeId,
                            'type' => InventoryMovement::TYPE_IN,
                            'qty_kg' => number_format($restock, 2, '.', ''),
                            'occurred_at' => $atIn,
                            'note' => 'Restock (auto)',
                            'meta' => null,
                        ]);

                        $balance[$typeId] += $restock;
                    } else {
                        continue;
                    }
                }

                if ($balance[$typeId] >= $qty) {
                    $at = $day->copy()->setTime(random_int(9, 20), random_int(0, 59), random_int(0, 59));

                    InventoryMovement::query()->create([
                        'item_type_id' => $typeId,
                        'type' => InventoryMovement::TYPE_OUT,
                        'qty_kg' => number_format($qty, 2, '.', ''),
                        'occurred_at' => $at,
                        'note' => 'Pemakaian',
                        'meta' => null,
                    ]);

                    $balance[$typeId] -= $qty;
                }
            }

            // ADJUST: 5% hari, 0.01–0.08 kg
            if (random_int(1, 100) <= 5) {
                $typeId = $typeIds[array_rand($typeIds)];
                $qty = random_int(1, 8) / 100;

                $at = $day->copy()->setTime(random_int(17, 21), random_int(0, 59), random_int(0, 59));

                InventoryMovement::query()->create([
                    'item_type_id' => $typeId,
                    'type' => InventoryMovement::TYPE_ADJUST,
                    'qty_kg' => number_format($qty, 2, '.', ''),
                    'occurred_at' => $at,
                    'note' => 'Penyesuaian',
                    'meta' => null,
                ]);

                $balance[$typeId] += $qty;
            }
        }

        // Sinkronkan saldo tersimpan di item_types (realtime balance)
        foreach ($balance as $typeId => $bal) {
            ItemType::query()
                ->whereKey($typeId)
                ->update(['balance_kg' => number_format(round((float) $bal, 2), 2, '.', '')]);
        }

        $this->command?->info('DemoInventorySeeder: data 5 bulan (kg) berhasil dibuat.');
    }
}
