<?php

namespace App\Models;

use App\Enums\LogEntryType;
use App\Enums\RequestStatus;
use Database\Factories\ServiceRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

#[Fillable([
    'reference_no', 'resident_id', 'service_type_id', 'purpose', 'status', 'fee_amount',
    'received_at', 'created_by', 'completed_by', 'completed_at',
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
            'status' => RequestStatus::class,
            'fee_amount' => 'decimal:2',
            'received_at' => 'date',
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
     * @return BelongsTo<ServiceType, $this>
     */
    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<RequestDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(RequestDocument::class)->orderBy('id');
    }

    /**
     * The chronological request log: status changes and follow-up notes.
     *
     * @return HasMany<RequestLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(RequestLog::class);
    }

    public function hasFee(): bool
    {
        return (float) $this->fee_amount > 0;
    }

    /**
     * What the resident still has to pay in cash; nothing once completed (FR-12).
     */
    public function amountDue(): string
    {
        return $this->status === RequestStatus::Completed ? '0.00' : (string) $this->fee_amount;
    }

    /**
     * The next status in the workflow, or null when completed.
     */
    public function nextStatus(): ?RequestStatus
    {
        return $this->status->next($this->hasFee());
    }

    /**
     * Whether this user may move the request to that status: it must be the next step,
     * and only the Administrator may set Completed.
     */
    public function canMoveTo(RequestStatus $status, User $user): bool
    {
        if (! $user->isPersonnel() || $status !== $this->nextStatus()) {
            return false;
        }

        return $status !== RequestStatus::Completed || $user->isAdministrator();
    }

    /**
     * Move to the next status and write the log entry (FR-04).
     */
    public function moveTo(RequestStatus $status, User $user): void
    {
        if (! $this->canMoveTo($status, $user)) {
            throw new InvalidArgumentException("{$user->role->label()} cannot move {$this->reference_no} to {$status->label()}.");
        }

        DB::transaction(function () use ($status, $user) {
            $previousStatus = $this->status;
            $completing = $status === RequestStatus::Completed;

            $this->update([
                'status' => $status,
                'completed_by' => $completing ? $user->id : null,
                'completed_at' => $completing ? now() : null,
            ]);

            $this->logs()->create([
                'user_id' => $user->id,
                'entry_type' => LogEntryType::Status,
                'old_status' => $previousStatus,
                'new_status' => $status,
            ]);
        });
    }

    /**
     * Add a follow-up note to the request log (FR-05).
     */
    public function addNote(string $note, User $user): void
    {
        $this->logs()->create([
            'user_id' => $user->id,
            'entry_type' => LogEntryType::Note,
            'note' => $note,
        ]);
    }

    /**
     * The status tracker: each step with its state and the time it was last reached.
     * Expects the logs relation to be loaded.
     *
     * @return list<array{status: RequestStatus, state: 'done'|'current'|'upcoming', reached_at: ?Carbon}>
     */
    public function trackerSteps(): array
    {
        $steps = RequestStatus::steps($this->hasFee());
        $currentIndex = array_search($this->status, $steps, true);

        return array_map(function (RequestStatus $status) use ($steps, $currentIndex): array {
            $index = array_search($status, $steps, true);

            return [
                'status' => $status,
                'state' => match (true) {
                    $index < $currentIndex || $this->status === RequestStatus::Completed => 'done',
                    $index === $currentIndex => 'current',
                    default => 'upcoming',
                },
                'reached_at' => $index <= $currentIndex
                    ? $this->logs->where('new_status', $status)->sortByDesc('id')->first()?->created_at
                    : null,
            ];
        }, $steps);
    }

    /**
     * Search and filters for the request list and reports (FR-06, FR-08).
     *
     * @param  Builder<ServiceRequest>  $query
     * @param  array{search?: ?string, service_type?: ?string, status?: ?string, date_from?: ?string, date_to?: ?string}  $filters
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $search = $filters['search'] ?? null;

        if (filled($search)) {
            $query->where(function (Builder $query) use ($search) {
                $query->where('reference_no', 'like', "%{$search}%")
                    ->orWhereHas('resident', fn (Builder $resident) => $resident->where('full_name', 'like', "%{$search}%"));
            });
        }

        if (filled($filters['service_type'] ?? null)) {
            $query->where('service_type_id', $filters['service_type']);
        }

        if ($status = RequestStatus::tryFrom($filters['status'] ?? '')) {
            $query->where('status', $status);
        }

        if (filled($filters['date_from'] ?? null)) {
            $query->whereDate('received_at', '>=', $filters['date_from']);
        }

        if (filled($filters['date_to'] ?? null)) {
            $query->whereDate('received_at', '<=', $filters['date_to']);
        }
    }
}
