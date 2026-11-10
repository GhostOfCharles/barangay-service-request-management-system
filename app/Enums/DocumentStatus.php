<?php

namespace App\Enums;

/**
 * Document-check information recorded with a service request.
 */
enum DocumentStatus: string
{
    case Complete = 'complete';
    case Incomplete = 'incomplete';
    case NotRequired = 'not_required';

    public function label(): string
    {
        return match ($this) {
            self::Complete => 'Complete',
            self::Incomplete => 'Incomplete',
            self::NotRequired => 'Not Required',
        };
    }
}
