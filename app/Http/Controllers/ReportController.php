<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Reports (FR-08, Screen 14): totals by status and by service type, with the cash collected
     * for requests marked Completed, filtered by date range, service type, and status.
     */
    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'service_type' => ['nullable', 'integer'],
            'status' => ['nullable', 'string'],
        ]) + ['date_from' => null, 'date_to' => null, 'service_type' => null, 'status' => null];

        if (! $request->hasAny(['date_from', 'date_to', 'service_type', 'status'])) {
            $filters['date_from'] = today()->startOfMonth()->toDateString();
            $filters['date_to'] = today()->endOfMonth()->toDateString();
        }

        $statusCounts = ServiceRequest::filter($filters)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $completed = RequestStatus::Completed->value;

        $byServiceType = ServiceRequest::filter($filters)
            ->join('service_types', 'service_types.id', '=', 'service_requests.service_type_id')
            ->selectRaw('service_types.name as name, count(*) as total')
            ->selectRaw('sum(case when service_requests.status = ? then 1 else 0 end) as completed', [$completed])
            ->selectRaw('sum(case when service_requests.status = ? then service_requests.fee_amount else 0 end) as cash_collected', [$completed])
            ->groupBy('service_types.name')
            ->orderBy('service_types.name')
            ->get();

        return view('reports.index', [
            'filters' => $filters,
            'statuses' => RequestStatus::cases(),
            'serviceTypes' => ServiceType::orderBy('name')->get(['id', 'name']),
            'totalCount' => $statusCounts->sum(),
            'statusCounts' => $statusCounts,
            'byServiceType' => $byServiceType,
            'cashCollected' => $byServiceType->sum('cash_collected'),
        ]);
    }
}
