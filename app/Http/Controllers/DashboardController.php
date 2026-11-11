<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Enums\TicketStatus;
use App\Enums\VerificationStatus;
use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Models\Ticket;
use Illuminate\View\View;

/**
 * Staff dashboard (FR-07, Screen 6): requests by status, pending verifications, open tickets,
 * and recent requests, without consolidating records by hand.
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'statusCounts' => ServiceRequest::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'completedTodayCount' => ServiceRequest::where('status', RequestStatus::Completed)
                ->whereDate('completed_at', today())
                ->count(),
            'pendingVerifications' => Resident::where('verification_status', VerificationStatus::Pending)->count(),
            'openTickets' => Ticket::where('status', TicketStatus::Open)->count(),
            'residentCount' => Resident::where('verification_status', VerificationStatus::Verified)->count(),
            'recentRequests' => ServiceRequest::with(['resident', 'serviceType'])->latest('id')->limit(5)->get(),
        ]);
    }
}
