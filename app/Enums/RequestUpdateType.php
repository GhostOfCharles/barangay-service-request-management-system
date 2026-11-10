<?php

namespace App\Enums;

/**
 * Kinds of entries kept in a service request history.
 */
enum RequestUpdateType: string
{
    case Created = 'created';
    case StatusChange = 'status_change';
    case FollowUp = 'follow_up';
}
