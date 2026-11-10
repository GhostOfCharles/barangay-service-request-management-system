<?php

namespace App\Models;

use App\Enums\RequestStatus;
use App\Enums\RequestUpdateType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry in a service request history: creation, status change, or follow-up action.
 */
#[Fillable(['user_id', 'type', 'from_status', 'to_status', 'action_date', 'note'])]
class RequestUpdate extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => RequestUpdateType::class,
            'from_status' => RequestStatus::class,
            'to_status' => RequestStatus::class,
            'action_date' => 'date',
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
