<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Database\Factories\ResidentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'full_name', 'address', 'purok', 'contact_number', 'valid_id_path',
    'verification_status', 'verified_by', 'verified_at', 'rejection_reason', 'privacy_consent_at',
])]
class Resident extends Model
{
    /** @use HasFactory<ResidentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'verification_status' => VerificationStatus::class,
            'verified_at' => 'datetime',
            'privacy_consent_at' => 'datetime',
        ];
    }

    /**
     * The login account, for residents who registered themselves.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * @return HasMany<ServiceRequest, $this>
     */
    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function isVerified(): bool
    {
        return $this->verification_status === VerificationStatus::Verified;
    }

    /**
     * Search by name, address, purok, or contact number (FR-06).
     *
     * @param  Builder<Resident>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $query->where(function (Builder $query) use ($term) {
            $query->where('full_name', 'like', "%{$term}%")
                ->orWhere('address', 'like', "%{$term}%")
                ->orWhere('purok', 'like', "%{$term}%")
                ->orWhere('contact_number', 'like', "%{$term}%");
        });
    }
}
