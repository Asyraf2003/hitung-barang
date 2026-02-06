<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Item;
use App\Models\ItemType;
use Carbon\Carbon;
use Illuminate\Http\Request;

final class AppController extends Controller
{
    private const PAGES = ['home', 'stock', 'input', 'history', 'reports', 'reports-detail'];

    public function shell()
    {
        return view('app.shell');
    }

    public function page(Request $request, string $page)
    {
        abort_unless(in_array($page, self::PAGES, true), 404);

        $data = [];

        if ($page === 'home') {
            $uiTz = 'Asia/Makassar';

            $mode = $request->query('mode', 'daily');
            if (!in_array($mode, ['daily', 'weekly', 'monthly'], true)) $mode = 'daily';
            $data['mode'] = $mode;

            $baseDate = $request->query('date') ?: now($uiTz)->toDateString();
            try {
                $anchor = Carbon::parse($baseDate, $uiTz);
            } catch (\Throwable $e) {
                $anchor = now($uiTz);
            }

            $itemId = (int) $request->query('item_id', 0);
            $typeId = (int) $request->query('item_type_id', 0);

            // If item_type_id provided, prefer it and derive item_id (for UI)
            if ($typeId > 0) {
                $t = ItemType::query()->find($typeId);
                if ($t) {
                    $itemId = (int) $t->item_id;
                } else {
                    $typeId = 0;
                }
            }

            // Options (for filter UI)
            $data['item_options'] = Item::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn($i) => ['id' => (int) $i->id, 'name' => (string) $i->name]);

            $typeOptQ = ItemType::query()->with('item:id,name')->orderBy('item_id')->orderBy('label');
            if ($itemId > 0) {
                $typeOptQ->where('item_id', $itemId);
            }
            $data['type_options'] = $typeOptQ->get()
                ->map(fn($t) => [
                    'id' => (int) $t->id,
                    'item_id' => (int) $t->item_id,
                    'label' => (string) (($t->item?->name ?? 'Barang') . ' • ' . $t->label),
                ]);

            $data['filters'] = [
                'item_id' => $itemId > 0 ? $itemId : '',
                'item_type_id' => $typeId > 0 ? $typeId : '',
            ];

            // Resolve filter type ids for queries
            $filterTypeIds = null;
            if ($typeId > 0) {
                $filterTypeIds = [$typeId];
            } elseif ($itemId > 0) {
                $filterTypeIds = ItemType::query()->where('item_id', $itemId)->pluck('id')->all();
            }

            // Window for chart (10 buckets)
            $labels = [];
            $keys = [];
            $rangeLabel = '10 hari';

            if ($mode === 'daily') {
                $endLocal = $anchor->copy()->endOfDay();
                $startLocal = $anchor->copy()->startOfDay()->subDays(9);
                $prevDate = $anchor->copy()->subDays(10)->toDateString();
                $nextDate = $anchor->copy()->addDays(10)->toDateString();
                $rangeLabel = '10 hari';

                for ($i = 0; $i < 10; $i++) {
                    $d = $startLocal->copy()->addDays($i);
                    $k = $d->format('Y-m-d');
                    $keys[] = $k;
                    $labels[] = $d->format('d/m');
                }
            } elseif ($mode === 'weekly') {
                $endLocal = $anchor->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
                $startLocal = $anchor->copy()->startOfWeek(Carbon::MONDAY)->startOfDay()->subWeeks(9);
                $prevDate = $anchor->copy()->subWeeks(10)->toDateString();
                $nextDate = $anchor->copy()->addWeeks(10)->toDateString();
                $rangeLabel = '10 minggu';

                for ($i = 0; $i < 10; $i++) {
                    $w = $startLocal->copy()->addWeeks($i)->startOfWeek(Carbon::MONDAY);
                    $k = $w->format('o-\WW');
                    $keys[] = $k;
                    $labels[] = 'W' . $w->isoWeek();
                }
            } else {
                $endLocal = $anchor->copy()->endOfMonth()->endOfDay();
                $startLocal = $anchor->copy()->startOfMonth()->startOfDay()->subMonths(9);
                $prevDate = $anchor->copy()->subMonths(10)->toDateString();
                $nextDate = $anchor->copy()->addMonths(10)->toDateString();
                $rangeLabel = '10 bulan';

                for ($i = 0; $i < 10; $i++) {
                    $m = $startLocal->copy()->addMonths($i)->startOfMonth();
                    $k = $m->format('Y-m');
                    $keys[] = $k;
                    $labels[] = $k;
                }
            }

            // Build nav urls (prev/next window)
            $params = ['mode' => $mode];
            if ($typeId > 0) $params['item_type_id'] = $typeId;
            elseif ($itemId > 0) $params['item_id'] = $itemId;

            $data['base_date'] = $anchor->toDateString();
            $data['range_label'] = $rangeLabel;
            $data['nav'] = [
                'prev' => '/app/home?' . http_build_query(array_merge($params, ['date' => $prevDate])),
                'next' => '/app/home?' . http_build_query(array_merge($params, ['date' => $nextDate])),
            ];

            // Query movements in window (stored in UTC), bucket in UI timezone
            $startUtc = $startLocal->copy()->setTimezone('UTC');
            $endUtc = $endLocal->copy()->setTimezone('UTC');

            $movQ = InventoryMovement::query()
                ->select(['type', 'qty_kg', 'occurred_at', 'item_type_id'])
                ->whereBetween('occurred_at', [$startUtc, $endUtc]);

            if ($filterTypeIds !== null) {
                if (count($filterTypeIds) === 0) {
                    $movQ->whereRaw('1=0');
                } else {
                    $movQ->whereIn('item_type_id', $filterTypeIds);
                }
            }

            $movs = $movQ->get();

            $idx = [];
            foreach ($keys as $i => $k) $idx[$k] = $i;

            $in = array_fill(0, 10, 0.0);
            $out = array_fill(0, 10, 0.0);

            foreach ($movs as $m) {
                $dt = $m->occurred_at;
                if (!$dt) continue;

                $local = $dt->copy()->setTimezone($uiTz);

                if ($mode === 'daily') {
                    $k = $local->format('Y-m-d');
                } elseif ($mode === 'weekly') {
                    $k = $local->copy()->startOfWeek(Carbon::MONDAY)->format('o-\WW');
                } else {
                    $k = $local->format('Y-m');
                }

                if (!isset($idx[$k])) continue;

                $i = $idx[$k];
                $qty = (float) $m->qty_kg;

                if ($m->type === InventoryMovement::TYPE_IN) {
                    $in[$i] += $qty;
                } elseif ($m->type === InventoryMovement::TYPE_OUT) {
                    $out[$i] += $qty;
                }
            }

            $in = array_map(fn($x) => round((float) $x, 2), $in);
            $out = array_map(fn($x) => round((float) $x, 2), $out);

            $data['charts'] = [
                'inOut' => ['labels' => $labels, 'in' => $in, 'out' => $out],
            ];

            // Summary: balance + today in/out (UI timezone)
            $sumBase = InventoryMovement::query();
            if ($filterTypeIds !== null) {
                if (count($filterTypeIds) === 0) {
                    $sumBase->whereRaw('1=0');
                } else {
                    $sumBase->whereIn('item_type_id', $filterTypeIds);
                }
            }

            $balance = round((float) ItemType::query()->sum('balance_kg'), 2);

            $nowLocal = now($uiTz);
            $todayStartUtc = $nowLocal->copy()->startOfDay()->setTimezone('UTC');
            $todayEndUtc = $nowLocal->copy()->endOfDay()->setTimezone('UTC');

            $todayOut = (float) (clone $sumBase)
                ->where('type', InventoryMovement::TYPE_OUT)
                ->whereBetween('occurred_at', [$todayStartUtc, $todayEndUtc])
                ->sum('qty_kg');

            $todayIn = (float) (clone $sumBase)
                ->where('type', InventoryMovement::TYPE_IN)
                ->whereBetween('occurred_at', [$todayStartUtc, $todayEndUtc])
                ->sum('qty_kg');

            $data['summary'] = [
                'total_balance_kg' => $balance,
                'today_in_kg' => round($todayIn, 2),
                'today_out_kg' => round($todayOut, 2),
            ];
        }


        if ($page === 'stock') {
            $rows = ItemType::query()
                ->with(['item:id,name'])
                ->orderBy('item_id')
                ->orderBy('label')
                ->get(['id', 'item_id', 'label', 'balance_kg'])
                ->map(fn (ItemType $t) => [
                    'item_type_id' => (int) $t->id,
                    'item_id' => (int) $t->item_id,
                    'barang' => (string) ($t->item?->name ?? 'Barang'),
                    'tipe' => (string) $t->label,
                    'balance_kg' => round((float) $t->balance_kg, 2),
                ])
                ->values();

            $data['items'] = $rows;
        }

        if ($page === 'input') {
            $data['item_options'] = Item::query()
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->map(fn($i) => ['id' => (int) $i->id, 'name' => (string) $i->name, 'code' => (string) $i->code]);

            $data['type_options'] = ItemType::query()
                ->with('item:id,name')
                ->orderBy('item_id')
                ->orderBy('label')
                ->get()
                ->map(fn($t) => [
                    'id' => (int) $t->id,
                    'item_id' => (int) $t->item_id,
                    'label' => (string) ($t->item?->name . ' • ' . $t->label),
                    'type_label' => (string) $t->label,
                ]);

            // Convenience: kalau ada item_type_id di query, set item_id untuk preselect
            $qTypeId = (int) $request->query('item_type_id', 0);
            if ($qTypeId > 0) {
                $t = ItemType::query()->find($qTypeId);
                if ($t) {
                    $data['prefill_item_id'] = (int) $t->item_id;
                }
            }
        }

        if ($page === 'history') {
            $tz = 'Asia/Makassar';

            $q = trim((string) $request->query('q', ''));
            $type = $request->query('type');
            $typeId = (int) $request->query('item_type_id', 0);
            $from = $request->query('from');
            $to   = $request->query('to');

            $query = InventoryMovement::query()
                ->with(['itemType.item'])
                ->orderByDesc('occurred_at')
                ->orderByDesc('id');

            $showVoided = $request->query('show_voided') === '1';

            if (!$showVoided) {
                $query->whereRaw("JSON_EXTRACT(meta, '$.voided_at') IS NULL");
            }

            if (in_array($type, ['IN','OUT'], true)) {
                $query->where('type', $type);
            } else {
                $type = '';
            }

            if ($typeId > 0) {
                $query->where('item_type_id', $typeId);
            }

            if ($from) {
                try {
                    $startLocal = Carbon::parse($from, $tz)->startOfDay();
                    $startUtc = $startLocal->copy()->setTimezone('UTC');
                    $query->where('occurred_at', '>=', $startUtc);
                } catch (\Throwable $e) {
                    $from = '';
                }
            }

            if ($to) {
                try {
                    $endLocal = Carbon::parse($to, $tz)->endOfDay();
                    $endUtc = $endLocal->copy()->setTimezone('UTC');
                    $query->where('occurred_at', '<=', $endUtc);
                } catch (\Throwable $e) {
                    $to = '';
                }
            }

            if ($q !== '') {
                $query->where(function ($qq) use ($q) {
                    $qq->where('note', 'like', "%{$q}%")
                        ->orWhereHas('itemType', function ($tq) use ($q) {
                            $tq->where('label', 'like', "%{$q}%")
                                ->orWhereHas('item', function ($iq) use ($q) {
                                    $iq->where('name', 'like', "%{$q}%");
                                });
                        });
                });
            }

            $rows = $query->limit(100)->get()->map(function (InventoryMovement $m) use ($tz) {
                $meta = $m->meta ?? [];
                $voidedAt = $meta['voided_at'] ?? null;
                $correctsId = $meta['corrects_id'] ?? null;

                return [
                    'id' => $m->id,
                    'type' => $m->type,
                    'qty_kg' => (float) $m->qty_kg,
                    'occurred_at' => $m->occurred_at?->copy()->setTimezone($tz)->format('d/m/Y H:i'),
                    'note' => $m->note,
                    'barang' => (string) ($m->itemType?->item?->name ?? ''),
                    'tipe' => (string) ($m->itemType?->label ?? ''),
                    'item_type_id' => (int) ($m->itemType?->id ?? 0),

                    // enterprise audit flags
                    'is_voided' => $voidedAt !== null,
                    'voided_at' => $voidedAt,
                    'corrects_id' => $correctsId ? (int) $correctsId : null,
                ];
            });

            $data['rows'] = $rows;

            $data['type_options'] = ItemType::query()
                ->with('item:id,name')
                ->orderBy('item_id')
                ->orderBy('label')
                ->get()
                ->map(fn($t) => [
                    'id' => (int) $t->id,
                    'label' => (string) (($t->item?->name ?? 'Barang') . ' • ' . $t->label),
                ]);

            $data['filters'] = [
                'q' => $q,
                'type' => $type,
                'show_voided' => $showVoided ? '1' : '',
                'item_type_id' => $typeId > 0 ? $typeId : '',
                'from' => $from ?: '',
                'to' => $to ?: '',
            ];
        }

        if ($page === 'reports') {
            $uiTz = 'Asia/Makassar';

            $mode = $request->query('mode', 'daily');
            if (!in_array($mode, ['daily', 'weekly', 'monthly'], true)) $mode = 'daily';

            $baseDate = $request->query('date') ?: now($uiTz)->toDateString();
            $base = Carbon::parse($baseDate, $uiTz);

            if ($mode === 'daily') {
                $start = $base->copy()->startOfDay();
                $end   = $base->copy()->endOfDay();
                $prevDate = $base->copy()->subDay()->toDateString();
                $nextDate = $base->copy()->addDay()->toDateString();
                $periodLabel = $base->toDateString();
            } elseif ($mode === 'weekly') {
                $start = $base->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
                $end   = $base->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
                $prevDate = $base->copy()->subWeek()->toDateString();
                $nextDate = $base->copy()->addWeek()->toDateString();
                $periodLabel = $start->toDateString().' s/d '.$end->toDateString();
            } else {
                $start = $base->copy()->startOfMonth()->startOfDay();
                $end   = $base->copy()->endOfMonth()->endOfDay();
                $prevDate = $base->copy()->subMonth()->toDateString();
                $nextDate = $base->copy()->addMonth()->toDateString();
                $periodLabel = $start->format('Y-m');
            }

            // IMPORTANT: storage UTC, jadi window query harus UTC
            $startUtc = $start->copy()->setTimezone('UTC');
            $endUtc   = $end->copy()->setTimezone('UTC');

            $rows = ItemType::query()
                ->join('items', 'items.id', '=', 'item_types.item_id')
                ->leftJoin('inventory_movements', 'item_types.id', '=', 'inventory_movements.item_type_id')
                ->select([
                    'item_types.id',
                    'items.name as item_name',
                    'item_types.label as type_label',
                ])
                ->selectRaw(
                    "COALESCE(SUM(CASE
                        WHEN inventory_movements.type = 'IN'
                        AND inventory_movements.occurred_at BETWEEN ? AND ?
                        AND JSON_EXTRACT(inventory_movements.meta, '$.voided_at') IS NULL
                        THEN inventory_movements.qty_kg ELSE 0 END),0) AS in_period_kg",
                    [$startUtc, $endUtc]
                )
                ->selectRaw(
                    "COALESCE(SUM(CASE
                        WHEN inventory_movements.type = 'OUT'
                        AND inventory_movements.occurred_at BETWEEN ? AND ?
                        AND JSON_EXTRACT(inventory_movements.meta, '$.voided_at') IS NULL
                        THEN inventory_movements.qty_kg ELSE 0 END),0) AS out_period_kg",
                    [$startUtc, $endUtc]
                )
                ->selectRaw(
                    "(
                    COALESCE(SUM(CASE
                        WHEN inventory_movements.type = 'IN'
                        AND inventory_movements.occurred_at <= ?
                        AND JSON_EXTRACT(inventory_movements.meta, '$.voided_at') IS NULL
                        THEN inventory_movements.qty_kg ELSE 0 END),0)
                    -
                    COALESCE(SUM(CASE
                        WHEN inventory_movements.type = 'OUT'
                        AND inventory_movements.occurred_at <= ?
                        AND JSON_EXTRACT(inventory_movements.meta, '$.voided_at') IS NULL
                        THEN inventory_movements.qty_kg ELSE 0 END),0)
                    ) AS balance_end_kg",
                    [$endUtc, $endUtc]
                )
                ->groupBy('item_types.id', 'items.name', 'item_types.label')
                ->orderBy('items.name')
                ->orderBy('item_types.label')
                ->get()
                ->map(fn($r) => [
                    'id' => (int) $r->id,
                    'barang' => (string) $r->item_name,
                    'tipe' => (string) $r->type_label,
                    'in_kg' => round((float) $r->in_period_kg, 2),
                    'out_kg' => round((float) $r->out_period_kg, 2),
                    'balance_end_kg' => round((float) $r->balance_end_kg, 2),
                ])
                ->filter(fn($x) => $x['in_kg'] != 0.0 || $x['out_kg'] != 0.0 || $x['balance_end_kg'] != 0.0)
                ->values();

            $data['mode'] = $mode;
            $data['base_date'] = $base->toDateString();
            $data['period_label'] = $periodLabel;
            $data['prev_date'] = $prevDate;
            $data['next_date'] = $nextDate;
            $data['rows'] = $rows;
            $data['totals'] = [
                'in_kg' => round((float) $rows->sum('in_kg'), 2),
                'out_kg' => round((float) $rows->sum('out_kg'), 2),
            ];
        }

        if ($page === 'reports-detail') {
            $uiTz = 'Asia/Makassar';

            $mode = $request->query('mode', 'daily');
            if (!in_array($mode, ['daily', 'weekly', 'monthly'], true)) $mode = 'daily';

            $baseDate = $request->query('date') ?: now($uiTz)->toDateString();
            $base = Carbon::parse($baseDate, $uiTz);

            if ($mode === 'daily') {
                $start = $base->copy()->startOfDay();
                $end   = $base->copy()->endOfDay();
                $periodLabel = $base->toDateString();
            } elseif ($mode === 'weekly') {
                $start = $base->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
                $end   = $base->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
                $periodLabel = $start->toDateString().' s/d '.$end->toDateString();
            } else {
                $start = $base->copy()->startOfMonth()->startOfDay();
                $end   = $base->copy()->endOfMonth()->endOfDay();
                $periodLabel = $start->format('Y-m');
            }

            // IMPORTANT: storage UTC, jadi window query harus UTC
            $startUtc = $start->copy()->setTimezone('UTC');
            $endUtc   = $end->copy()->setTimezone('UTC');

            $typeId = (int) $request->query('item_type_id', 0);
            abort_if($typeId <= 0, 404);

            $type = ItemType::query()->with('item')->findOrFail($typeId);

            $rows = InventoryMovement::query()
                ->where('item_type_id', $typeId)
                ->whereBetween('occurred_at', [$startUtc, $endUtc])
                ->whereRaw("JSON_EXTRACT(meta, '$.voided_at') IS NULL")
                ->orderByDesc('occurred_at')
                ->orderByDesc('id')
                ->limit(300)
                ->get()
                ->map(function (InventoryMovement $m) use ($uiTz) {
                    $meta = $m->meta ?? [];
                    $correctsId = $meta['corrects_id'] ?? null;

                    return [
                        'id' => $m->id,
                        'type' => $m->type,
                        'qty_kg' => (float) $m->qty_kg,
                        'occurred_at' => $m->occurred_at?->copy()->setTimezone($uiTz)->format('d/m/Y H:i'),
                        'note' => $m->note,
                        'corrects_id' => $correctsId ? (int) $correctsId : null,
                    ];
                });

            $inKg = (float) InventoryMovement::query()
                ->where('item_type_id', $typeId)
                ->where('type', 'IN')
                ->whereBetween('occurred_at', [$startUtc, $endUtc])
                ->whereRaw("JSON_EXTRACT(meta, '$.voided_at') IS NULL")
                ->sum('qty_kg');

            $outKg = (float) InventoryMovement::query()
                ->where('item_type_id', $typeId)
                ->where('type', 'OUT')
                ->whereBetween('occurred_at', [$startUtc, $endUtc])
                ->whereRaw("JSON_EXTRACT(meta, '$.voided_at') IS NULL")
                ->sum('qty_kg');

            $balEnd = (float) InventoryMovement::query()
                ->where('item_type_id', $typeId)
                ->where('occurred_at', '<=', $endUtc)
                ->whereRaw("JSON_EXTRACT(meta, '$.voided_at') IS NULL")
                ->selectRaw("(
                    COALESCE(SUM(CASE WHEN type = 'IN' THEN qty_kg ELSE 0 END),0)
                    -
                    COALESCE(SUM(CASE WHEN type = 'OUT' THEN qty_kg ELSE 0 END),0)
                ) AS bal")
                ->value('bal');


            $data['mode'] = $mode;
            $data['base_date'] = $base->toDateString();
            $data['period_label'] = $periodLabel;

            $data['item_type'] = [
                'id' => (int) $type->id,
                'barang' => (string) ($type->item?->name ?? 'Barang'),
                'tipe' => (string) $type->label,
            ];

            $data['totals'] = [
                'in_kg' => round($inKg, 2),
                'out_kg' => round($outKg, 2),
                'balance_end_kg' => round($balEnd, 2),
            ];

            $data['rows'] = $rows;
        }

        // PJAX
        if ($request->header('X-PJAX') === '1') {
            return view("app.pages.$page", $data);
        }

        $initialHtml = view("app.pages.$page", $data)->render();

        return view('app.shell', [
            'initialHtml' => $initialHtml,
        ]);
    }
}
