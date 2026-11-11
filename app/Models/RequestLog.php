<?php

namespace App\Models;

use App\Enums\LogEntryType;
use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry in a request's chronological log: a status change or a follow-up note (FR-04, FR-05).
 */
#[Fillable(['user_id', 'entry_type', 'old_status', 'new_status', 'note'])]
class RequestLog extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entry_type' => LogEntryType::class,
            'old_status' => RequestStatus::class,
            'new_status' => RequestStatus::class,
        ];
    }

    /**
     * @return BelongsTo<ServiceRequest, $this>
     */
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
