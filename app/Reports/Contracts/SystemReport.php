<?php

namespace App\Reports\Contracts;

use App\User;
use Illuminate\Http\Request;

interface SystemReport
{
    public function slug(): string;

    public function title(): string;

    public function category(): string;

    /** Navigation/icon glyph shown on the reports index. */
    public function icon(): string;

    public function description(): string;

    /** Whether the given user is allowed to view this report. */
    public function allows(User $user): bool;

    /**
     * Filter definitions consumed by the shared report view:
     * ['key', 'label', 'type' => date|month|select, 'default' => mixed].
     */
    public function filters(): array;

    /** Select-style filter option values, keyed by filter key. */
    public function filterOptions(User $user, string $key): array;

    /**
     * Build the report. Returns a structured result:
     * ['kpis' => [...], 'sections' => [['title','note','columns','rows']], 'as_of' => '...'].
     */
    public function generate(Request $request, User $user, array $filterValues): array;
}