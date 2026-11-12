<?php

namespace App\Enums;

/**
 * Login account status (users.status). Resident accounts start as Pending until
 * residency is verified (FR-09); deactivated personnel accounts are Inactive.
 */
enum AccountStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Rejected = 'rejected';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Verification',
            self::Active => 'Active',
            self::Rejected => 'Rejected',
            self::Inactive => 'Inactive',
        };
    }
}
