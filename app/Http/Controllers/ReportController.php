<?php

namespace App\Http\Controllers;

use App\Models\ReportDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $reports = ReportDefinition::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('reports.index', compact('reports'));
    }

    /**
     * Generic data endpoint for report-builder reports. Reads the configured
     * builder_columns/filters/group_by from the ReportDefinition, queries the
     * referenced tables and returns JSON rows the crew frontend can render.
     */
    public function builderData(string $slug, Request $request)
    {
        $report = ReportDefinition::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $columns = $report->builder_columns ?? [];
        $filters = $report->builder_filters ?? [];
        $groupBy = $report->builder_group_by;
        $layout = $report->builder_layout ?? 'table';

        if (empty($columns)) {
            return response()->json(['error' => 'This report has no columns configured.'], 422);
        }

        $tables = collect($columns)->pluck('table')
            ->merge(collect($filters)->pluck('table'))
            ->unique()->filter()->values();

        $primary = $tables->first();
        if (! $primary || ! Schema::hasTable($primary)) {
            return response()->json(['error' => "Configured table '{$primary}' does not exist."], 422);
        }

        $query = DB::table($primary);
        $primaryCols = Schema::getColumnListing($primary);

        foreach ($tables->slice(1) as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $cols = Schema::getColumnListing($table);
            $shared = array_values(array_intersect($primaryCols, $cols));
            if (empty($shared)) {
                continue;
            }
            $query->leftJoin($table, "$primary.{$shared[0]}", '=', "$table.{$shared[0]}");
        }

        $selects = [];
        foreach ($columns as $i => $col) {
            $table = $col['table'] ?? $primary;
            $column = $col['column'] ?? null;
            if (! $column) {
                continue;
            }
            $alias = "__c{$i}";
            $selects[$alias] = $col['label'] ?? $col['column'] ?? $column;
            $query->addSelect("$table.$column as $alias");
        }

        if (empty($selects)) {
            return response()->json(['error' => 'No valid columns configured for this report.'], 422);
        }

        foreach ($filters as $i => $filter) {
            $value = $request->query("f$i");
            if ($value === null || $value === '') {
                continue;
            }
            $table = $filter['table'] ?? $primary;
            $column = $filter['column'] ?? null;
            if ($column && Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                $query->where("$table.$column", $value);
            }
        }

        if ($groupBy) {
            $gb = $groupBy;
            if (! str_contains($gb, '.')) {
                $gb = "$primary.$gb";
            }
            $query->groupBy($gb);
        }

        try {
            $rows = $query->limit(500)->get();
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Query failed: '.$e->getMessage()], 422);
        }

        $resultColumns = [];
        foreach ($selects as $alias => $label) {
            $resultColumns[] = ['label' => $label, 'key' => $alias];
        }

        $resultRows = $rows->map(fn ($row) => (array) $row)->values();

        return response()->json([
            'label' => $report->name,
            'layout' => $layout,
            'columns' => $resultColumns,
            'rows' => $resultRows,
        ]);
    }

    public function dailyStatus()
    {
        return redirect('/reports');
    }

    public function monthlyRegister()
    {
        return redirect('/reports');
    }

    public function utilization()
    {
        return redirect('/reports');
    }

    public function absence()
    {
        return redirect('/reports');
    }

    public function printable()
    {
        return redirect('/reports');
    }
}
