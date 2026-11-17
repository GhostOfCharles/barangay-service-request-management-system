<?php

namespace App\Http\Controllers;

use App\Models\RequestDocument;
use App\Models\Resident;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves uploaded files from the private default disk, only through authenticated, authorized actions (NFR Security).
 * Locally that is storage/app/private; on Laravel Cloud it is a private Object Storage bucket set as the default disk.
 */
class PrivateFileController extends Controller
{
    public function validId(Resident $resident): StreamedResponse
    {
        Gate::authorize('view-resident-files', $resident);
        abort_unless($resident->valid_id_path && Storage::disk()->exists($resident->valid_id_path), 404);

        return Storage::disk()->response($resident->valid_id_path, 'valid-id-'.$resident->id.'.'.pathinfo($resident->valid_id_path, PATHINFO_EXTENSION));
    }

    public function requestDocument(RequestDocument $document): StreamedResponse
    {
        Gate::authorize('view-request-document', $document);
        abort_unless(Storage::disk()->exists($document->file_path), 404);

        return Storage::disk()->response($document->file_path, $document->original_name);
    }
}
