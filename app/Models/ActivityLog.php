<?php

namespace App\Models;

use App\Enums\ActivityAction;
use App\Enums\UserRole;
use Database\Factories\ActivityLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

/**
 * Activity Log entry: who changed what, on which record, when, from which IP address, and the
 * before → after values. Only Staff and Administrator actions are recorded. Entries are
 * append-only: the model refuses updates and deletes.
 */
#[Fillable([
    'user_id', 'user_name', 'user_role', 'action', 'subject_type', 'subject_id', 'subject_label',
    'description', 'changes', 'ip_address',
])]
class ActivityLog extends Model
{
    /** @use HasFactory<ActivityLogFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => ActivityAction::class,
            'user_role' => UserRole::class,
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Activity log entries cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Activity log entries cannot be deleted.'));
    }

    /**
     * Record an action by a Staff or Administrator account. Residents' own actions are not recorded.
     *
     * @param  array<string, array{0: ?string, 1: ?string}>  $changes  field label => [before, after]
     */
    public static function record(User $user, ActivityAction $action, string $description, ?Model $subject = null, array $changes = []): ?self
    {
        if (! $user->isPersonnel()) {
            return null;
        }

        return self::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_role' => $user->role,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'subject_label' => $subject ? self::labelFor($subject) : null,
            'description' => $description,
            'changes' => $changes ?: null,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Before → after pairs for the fields that actually changed.
     *
     * @param  array<string, ?string>  $before  field label => value
     * @param  array<string, ?string>  $after  field label => value
     * @return array<string, array{0: ?string, 1: ?string}>
     */
    public static function differences(array $before, array $after): array
    {
        $changes = [];

        foreach ($after as $field => $value) {
            if (($before[$field] ?? null) !== $value) {
                $changes[$field] = [$before[$field] ?? null, $value];
            }
        }

        return $changes;
    }

    /**
     * The name people know a record by, e.g. SR-2026-0005 or a resident's name.
     */
    public static function labelFor(Model $subject): string
    {
        return match (true) {
            $subject instanceof ServiceRequest => $subject->reference_no,
            $subject instanceof Ticket => $subject->ticket_no,
            $subject instanceof Resident => $subject->full_name,
            $subject instanceof ServiceType => $subject->name,
            $subject instanceof User => $subject->username,
            default => class_basename($subject).' #'.$subject->getKey(),
        };
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Link to the affected record, when it still exists and has a page.
     */
    public function subjectUrl(): ?string
    {
        $subject = $this->subject;

        return match (true) {
            $subject instanceof ServiceRequest => route('service-requests.show', $subject),
            $subject instanceof Ticket => route('tickets.index', ['ticket' => $subject->id]),
            $subject instanceof Resident => route('residents.show', $subject),
            $subject instanceof ServiceType => route('admin.service-types.index', ['edit' => $subject->id]),
            $subject instanceof User && ! $subject->isResident() => route('admin.users.edit', $subject),
            default => null,
        };
    }

    /**
     * Filter by user, action, date range, and a search over the record, user, and description.
     *
     * @param  array{user?: ?string, action?: ?string, date_from?: ?string, date_to?: ?string, search?: ?string}  $filters
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['user'] ?? null, fn (Builder $query, $userId) => $query->where('user_id', $userId))
            ->when(ActivityAction::tryFrom((string) ($filters['action'] ?? '')), fn (Builder $query, $action) => $query->where('action', $action))
            ->when($filters['date_from'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when($filters['search'] ?? null, function (Builder $query, $term) {
                $query->where(function (Builder $query) use ($term) {
                    $query->where('subject_label', 'like', "%{$term}%")
                        ->orWhere('user_name', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            });
    }
}
