<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\RequestCategory;
use App\Enums\RequestStatus;
use App\Enums\RequestUpdateType;
use Database\Factories\ServiceRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'reference_no', 'resident_id', 'category', 'description', 'document_status',
    'date_received', 'status', 'completed_at', 'created_by',
])]
class ServiceRequest extends Model
{
    /** @use HasFactory<ServiceRequestFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => RequestCategory::class,
            'document_status' => DocumentStatus::class,
            'status' => RequestStatus::class,
            'date_received' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Resident, $this>
     */
    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Chronological status and follow-up history (FR-05).
     *
     * @return HasMany<RequestUpdate, $this>
     */
    public function updates(): HasMany
    {
        return $this->hasMany(RequestUpdate::class);
    }

    /**
     * Create a request with a unique reference number and its first history entry.
     *
     * @param  array{resident_id: int, category: string, description: string, document_status: string, date_received: string}  $attributes
     */
    public static function record(array $attributes, User $staff): self
    {
        return DB::transaction(function () use ($attributes, $staff) {
            $serviceRequest = self::create([
                ...$attributes,
                'reference_no' => self::nextReferenceNo(),
                'status' => RequestStatus::Pending,
                'created_by' => $staff->id,
            ]);

            $serviceRequest->updates()->create([
                'user_id' => $staff->id,
                'type' => RequestUpdateType::Created,
                'to_status' => RequestStatus::Pending,
                'action_date' => $serviceRequest->date_received,
                'note' => 'Request recorded.',
            ]);

            return $serviceRequest;
        });
    }

    /**
     * Next reference number in the format SR-YYYY-0001 (resets every year).
     */
    public static function nextReferenceNo(): string
    {
        $prefix = 'SR-'.now()->year.'-';

        $lastReference = self::query()
            ->where('reference_no', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('reference_no')
            ->value('reference_no');

        $nextNumber = $lastReference ? ((int) substr($lastReference, -4)) + 1 : 1;

        return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Change the current status and record it in the request history (FR-04).
     */
    public function changeStatus(RequestStatus $newStatus, User $staff, ?string $note = null, ?Carbon $actionDate = null): void
    {
        if ($newStatus === $this->status) {
            return;
        }

        DB::transaction(function () use ($newStatus, $staff, $note, $actionDate) {
            $previousStatus = $this->status;

            $this->update([
                'status' => $newStatus,
                'completed_at' => $newStatus === RequestStatus::Completed ? now() : null,
            ]);

            $this->updates()->create([
                'user_id' => $staff->id,
                'type' => RequestUpdateType::StatusChange,
                'from_status' => $previousStatus,
                'to_status' => $newStatus,
                'action_date' => $actionDate ?? today(),
                'note' => $note,
            ]);
        });
    }

    /**
     * Apply the search and filter criteria used by the request list and reports (FR-06).
     *
     * @param  Builder<ServiceRequest>  $query
     * @param  array{search?: ?string, status?: ?string, category?: ?string, date_from?: ?string, date_to?: ?string}  $filters
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $search = $filters['search'] ?? null;

        if (filled($search)) {
            $query->where(function (Builder $query) use ($search) {
                $query->where('reference_no', 'like', "%{$search}%")
                    ->orWhereHas('resident', fn (Builder $resident) => $resident->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = RequestStatus::tryFrom($filters['status'] ?? '')) {
            $query->where('status', $status);
        }

        if ($category = RequestCategory::tryFrom($filters['category'] ?? '')) {
            $query->where('category', $category);
        }

        if (filled($filters['date_from'] ?? null)) {
            $query->whereDate('date_received', '>=', $filters['date_from']);
        }

        if (filled($filters['date_to'] ?? null)) {
            $query->whereDate('date_received', '<=', $filters['date_to']);
        }
    }
}
