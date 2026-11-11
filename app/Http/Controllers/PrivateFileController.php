<?php

namespace App\Http\Controllers;

use App\Models\RequestDocument;
use App\Models\Resident;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves uploaded files from the private disk, only through authenticated, authorized actions (NFR Security).
 */
class PrivateFileController extends Controller
{
    public function validId(Resident $resident): StreamedResponse
    {
        Gate::authorize('view-resident-files', $resident);
        abort_unless($resident->valid_id_path && Storage::disk('local')->exists($resident->valid_id_path), 404);

        return Storage::disk('local')->response($resident->valid_id_path, 'valid-id-'.$resident->id.'.'.pathinfo($resident->valid_id_path, PATHINFO_EXTENSION));
    }

    public function requestDocument(RequestDocument $document): StreamedResponse
    {
        Gate::authorize('view-request-document', $document);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->response($document->file_path, $document->original_name);
    }
}
