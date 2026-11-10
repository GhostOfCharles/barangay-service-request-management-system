<?php

namespace App\Http\Controllers;

use App\Enums\RequestCategory;
use App\Enums\RequestStatus;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Basic report filtered by date range, category, and status (FR-08).
     */
    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'category' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
        ]) + [
            'date_from' => null,
            'date_to' => null,
            'category' => null,
            'status' => null,
        ];

        if (! $request->hasAny(['date_from', 'date_to', 'category', 'status'])) {
            $filters['date_from'] = today()->startOfMonth()->toDateString();
            $filters['date_to'] = today()->toDateString();
        }

        $statusCounts = ServiceRequest::filter($filters)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $categoryCounts = ServiceRequest::filter($filters)
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        return view('reports.index', [
            'filters' => $filters,
            'statuses' => RequestStatus::cases(),
            'categories' => RequestCategory::cases(),
            'totalCount' => $statusCounts->sum(),
            'statusCounts' => $statusCounts,
            'categoryCounts' => $categoryCounts,
            'results' => ServiceRequest::with('resident')
                ->filter($filters)
                ->orderBy('date_received')
                ->orderBy('id')
                ->get(),
        ]);
    }
}
