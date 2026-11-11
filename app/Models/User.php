<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

#[Fillable(['name', 'username', 'role', 'status', 'position', 'must_change_password', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => AccountStatus::class,
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * The resident record of a resident account.
     *
     * @return HasOne<Resident, $this>
     */
    public function resident(): HasOne
    {
        return $this->hasOne(Resident::class);
    }

    public function isAdministrator(): bool
    {
        return $this->role === UserRole::Administrator;
    }

    public function isResident(): bool
    {
        return $this->role === UserRole::Resident;
    }

    /**
     * Administrator or Staff: the people who process requests.
     */
    public function isPersonnel(): bool
    {
        return $this->role->isPersonnel();
    }

    public function isActive(): bool
    {
        return $this->status === AccountStatus::Active;
    }

    /**
     * Initials shown in the sidebar avatar, e.g. "SC" for "Sec. Cruz".
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->replaceMatches('/[^A-Za-z ]/', '')
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');
    }
}
