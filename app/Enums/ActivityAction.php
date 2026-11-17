<?php

namespace App\Enums;

/**
 * Actions recorded in the Activity Log (activity_logs.action): every change made by Staff or the Administrator.
 */
enum ActivityAction: string
{
    case RequestCreated = 'request_created';
    case RequestStatusChanged = 'request_status_changed';
    case RequestCompleted = 'request_completed';
    case RequestNoteAdded = 'request_note_added';
    case ResidentAdded = 'resident_added';
    case ResidentUpdated = 'resident_updated';
    case ResidentApproved = 'resident_approved';
    case ResidentRejected = 'resident_rejected';
    case TicketReplied = 'ticket_replied';
    case TicketClosed = 'ticket_closed';
    case ServiceTypeCreated = 'service_type_created';
    case ServiceTypeUpdated = 'service_type_updated';
    case ServiceTypeActivated = 'service_type_activated';
    case ServiceTypeDeactivated = 'service_type_deactivated';
    case UserCreated = 'user_created';
    case UserUpdated = 'user_updated';
    case PasswordReset = 'password_reset';
    case PasswordChanged = 'password_changed';

    public function label(): string
    {
        return match ($this) {
            self::RequestCreated => 'Request created',
            self::RequestStatusChanged => 'Status changed',
            self::RequestCompleted => 'Request completed',
            self::RequestNoteAdded => 'Note added',
            self::ResidentAdded => 'Resident added',
            self::ResidentUpdated => 'Resident edited',
            self::ResidentApproved => 'Registration approved',
            self::ResidentRejected => 'Registration rejected',
            self::TicketReplied => 'Ticket replied',
            self::TicketClosed => 'Ticket closed',
            self::ServiceTypeCreated => 'Service type added',
            self::ServiceTypeUpdated => 'Service type edited',
            self::ServiceTypeActivated => 'Service type activated',
            self::ServiceTypeDeactivated => 'Service type deactivated',
            self::UserCreated => 'Account created',
            self::UserUpdated => 'Account edited',
            self::PasswordReset => 'Password reset',
            self::PasswordChanged => 'Own password changed',
        };
    }

    /**
     * Area of the system, used to group the action filter.
     */
    public function area(): string
    {
        return match ($this) {
            self::RequestCreated, self::RequestStatusChanged, self::RequestCompleted, self::RequestNoteAdded => 'Service requests',
            self::ResidentAdded, self::ResidentUpdated, self::ResidentApproved, self::ResidentRejected => 'Residents',
            self::TicketReplied, self::TicketClosed => 'Tickets',
            self::ServiceTypeCreated, self::ServiceTypeUpdated, self::ServiceTypeActivated, self::ServiceTypeDeactivated => 'Service types',
            self::UserCreated, self::UserUpdated, self::PasswordReset, self::PasswordChanged => 'Accounts',
        };
    }
}
