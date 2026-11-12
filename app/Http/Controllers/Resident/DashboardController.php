<?php

namespace App\Http\Controllers\Resident;

use App\Enums\RequestStatus;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The resident dashboard, "My Requests" (FR-11, Screen 3): only the resident's own requests,
 * with reference numbers, a status tracker, and the amount due. No SMS or email notifications.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $resident = $request->user()->resident;

        $serviceRequests = $resident->serviceRequests()
            ->with(['serviceType', 'logs'])
            ->latest('received_at')
            ->latest('id')
            ->get();

        $tracked = $serviceRequests->firstWhere('id', (int) $request->query('track'))
            ?? $serviceRequests->first(fn ($serviceRequest) => $serviceRequest->status !== RequestStatus::Completed)
            ?? $serviceRequests->first();

        return view('resident.dashboard', [
            'resident' => $resident,
            'serviceRequests' => $serviceRequests,
            'tracked' => $tracked,
            'activeCount' => $serviceRequests->where('status', '!==', RequestStatus::Completed)->count(),
            'completedCount' => $serviceRequests->where('status', RequestStatus::Completed)->count(),
            'amountDue' => $serviceRequests->sum(fn ($serviceRequest) => (float) $serviceRequest->amountDue()),
            'openTickets' => $resident->tickets()->where('status', '!=', TicketStatus::Closed)->count(),
        ]);
    }
}
