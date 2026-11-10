<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\Resident;
use App\Models\ServiceRequest;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * At-a-glance view of pending and completed work (FR-07).
     */
    public function __invoke(): View
    {
        return view('dashboard', [
            'pendingCount' => ServiceRequest::where('status', RequestStatus::Pending)->count(),
            'inProgressCount' => ServiceRequest::where('status', RequestStatus::InProgress)->count(),
            'completedTodayCount' => ServiceRequest::where('status', RequestStatus::Completed)
                ->whereDate('completed_at', today())
                ->count(),
            'residentCount' => Resident::count(),
            'recentRequests' => ServiceRequest::with('resident')->latest('id')->limit(5)->get(),
        ]);
    }
}
