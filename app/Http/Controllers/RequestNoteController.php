<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

        return redirect()->route('service-requests.show', $serviceRequest)
            ->with('success', 'Note added to the request log.');
    }
}
