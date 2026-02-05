<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ItemType;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

final class ReportPdfController extends Controller
{
    public function period(Request $request)
    {
        // UI timezone (periode & label) harus konsisten dengan app UI
        $uiTz = 'Asia/Makassar';

        $mode = $request->query('mode', 'daily');
        if (!in_array($mode, ['daily', 'weekly', 'monthly'], true)) $mode = 'daily';

        $baseDate = $request->query('date') ?: now($uiTz)->toDateString();
        $base = Carbon::parse($baseDate, $uiTz);

        if ($mode === 'daily') {
            $start = $base->copy()->startOfDay();
            $end   = $base->copy()->endOfDay();
            $periodText = $base->toDateString();
            $fileLabel = $base->toDateString();
        } elseif ($mode === 'weekly') {
            $start = $base->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
            $end   = $base->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
            $periodText = $start->toDateString().' s/d '.$end->toDateString();
            $fileLabel = $start->toDateString().'_to_'.$end->toDateString();
        } else {
            $start = $base->copy()->startOfMonth()->startOfDay();
            $end   = $base->copy()->endOfMonth()->endOfDay();
            $periodText = $start->format('Y-m');
            $fileLabel = $start->format('Y-m');
        }

        // IMPORTANT: occurred_at tersimpan UTC, jadi window query harus UTC
        $startUtc = $start->copy()->setTimezone('UTC');
        $endUtc   = $end->copy()->setTimezone('UTC');

        $rows = ItemType::query()
            ->join('items', 'items.id', '=', 'item_types.item_id')
            ->leftJoin('inventory_movements', 'item_types.id', '=', 'inventory_movements.item_type_id')
            ->select([
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
            ->groupBy('items.name', 'item_types.label')
            ->orderBy('items.name')
            ->orderBy('item_types.label')
            ->get()
            ->map(fn($r) => [
                'barang' => (string) $r->item_name,
                'tipe' => (string) $r->type_label,
                'in_kg' => round((float) $r->in_period_kg, 2),
                'out_kg' => round((float) $r->out_period_kg, 2),
                'balance_end_kg' => round((float) $r->balance_end_kg, 2),
            ])
            ->filter(fn($x) => $x['in_kg'] != 0.0 || $x['out_kg'] != 0.0 || $x['balance_end_kg'] != 0.0)
            ->values();

        $data = [
            'title' => 'Laporan '.strtoupper($mode),
            'period_text' => $periodText,
            'generated_at' => now($uiTz)->format('d/m/Y H:i'),
            'rows' => $rows,
            'totals' => [
                'in_kg' => round((float) $rows->sum('in_kg'), 2),
                'out_kg' => round((float) $rows->sum('out_kg'), 2),
                'balance_end_kg' => round((float) $rows->sum('balance_end_kg'), 2),
            ],
        ];

        $filename = "report-{$mode}-{$fileLabel}.pdf";

        return Pdf::loadView('pdf.report-period', $data)
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }
}
