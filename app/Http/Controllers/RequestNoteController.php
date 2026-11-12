<?php

namespace App\Http\Controllers;

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\ServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Add Note on Request Details: a follow-up note saved to the request log (FR-05).
 */
class RequestNoteController extends Controller
{
    public function __invoke(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $validated = $request->validateWithBag('addNote', [
            'note' => ['required', 'string', 'max:2000'],
        ], [
            'note.required' => 'Please write the follow-up note before adding it.',
        ]);

        $serviceRequest->addNote($validated['note'], $request->user());

        ActivityLog::record($request->user(), ActivityAction::RequestNoteAdded,
            "Added a note to {$serviceRequest->reference_no}: \"".Str::limit($validated['note'], 300).'"', $serviceRequest);

        return redirect()->route('service-requests.show', $serviceRequest)
            ->with('success', 'Note added to the request log.');
    }
}
