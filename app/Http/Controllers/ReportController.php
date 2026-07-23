<?php

namespace App\Http\Controllers;

use App\Models\ReportDefinition;
use Illuminate\Http\Request;

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
