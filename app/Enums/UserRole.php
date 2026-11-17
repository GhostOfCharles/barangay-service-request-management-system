<?php

namespace App\Enums;

/**
 * The three application roles (FR-01).
 */
enum UserRole: string
{
    case Administrator = 'administrator';
    case Staff = 'staff';
    case Resident = 'resident';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Staff => 'Staff',
            self::Resident => 'Resident',
        };
    }

    /**
     * Administrator and Staff process requests and maintain records.
     */
    public function isPersonnel(): bool
    {
        return $this !== self::Resident;
    }

    /**
     * Roles the Administrator can give in User Management; resident accounts come only from registration.
     *
     * @return list<self>
     */
    public static function personnelRoles(): array
    {
        return [self::Administrator, self::Staff];
    }
}
