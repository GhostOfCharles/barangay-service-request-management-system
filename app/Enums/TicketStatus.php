<?php

namespace App\Enums;

/**
 * Issue ticket statuses (FR-13). A staff reply sets Answered; a resident reply sets Open again.
 */
enum TicketStatus: string
{
    case Open = 'open';
    case Answered = 'answered';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Answered => 'Answered',
            self::Closed => 'Closed',
        };
    }
}
