<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActivityAction;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Activity Log (Administrator only): every change made by Staff and the Administrator, read-only,
 * with filters, search, print, and CSV export.
 */
class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        return view('admin.activity-log.index', [
            'entries' => ActivityLog::query()
                ->with('subject')
                ->filter($filters)
                ->latest('created_at')
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'filters' => $filters,
            'users' => User::where('role', '!=', 'resident')->orderBy('name')->get(['id', 'name', 'username']),
            'actionGroups' => collect(ActivityAction::cases())->groupBy(fn (ActivityAction $action) => $action->area()),
        ]);
    }

    /**
     * Download the filtered log as a CSV file (opens in Excel).
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);

        return response()->streamDownload(function () use ($filters) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF"); // so Excel reads ₱ and ñ correctly
            fputcsv($output, ['Date and time', 'User', 'Role', 'Action', 'Record', 'Description', 'Changes', 'IP address']);

            ActivityLog::query()->filter($filters)->latest('created_at')->latest('id')
                ->lazy(200)
                ->each(fn (ActivityLog $entry) => fputcsv($output, [
                    $entry->created_at->format('Y-m-d H:i:s'),
                    $entry->user_name,
                    $entry->user_role->label(),
                    $entry->action->label(),
                    $entry->subject_label,
                    $entry->description,
                    collect($entry->changes ?? [])
                        ->map(fn (array $change, string $field) => "{$field}: ".($change[0] ?? '—').' → '.($change[1] ?? '—'))
                        ->implode('; '),
                    $entry->ip_address,
                ]));

            fclose($output);
        }, 'activity-log-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{user: ?string, action: ?string, date_from: ?string, date_to: ?string, search: ?string}
     */
    private function filters(Request $request): array
    {
        $request->validate([
            'user' => ['nullable', 'integer'],
            'action' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        return [
            'user' => $request->query('user'),
            'action' => $request->query('action'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'search' => $request->query('search'),
        ];
    }
}
