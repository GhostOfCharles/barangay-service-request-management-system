<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A file uploaded for a request, kept on the private disk (FR-10).
 * The document name is copied so the record stays readable if the checklist changes later.
 */
#[Fillable(['document_requirement_id', 'document_name', 'file_path', 'original_name', 'file_size'])]
class RequestDocument extends Model
{
    /**
     * @return BelongsTo<ServiceRequest, $this>
     */
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    /**
     * "1.2 MB" or "640 KB".
     */
    public function readableSize(): string
    {
        return $this->file_size >= 1048576
            ? number_format($this->file_size / 1048576, 1).' MB'
            : max(1, (int) round($this->file_size / 1024)).' KB';
    }
}
