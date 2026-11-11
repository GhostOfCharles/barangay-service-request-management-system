<?php

namespace App\Enums;

/**
 * Kinds of request log entries (request_logs.entry_type).
 */
enum LogEntryType: string
{
    case Status = 'status';
    case Note = 'note';
}
