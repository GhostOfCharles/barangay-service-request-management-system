<?php

namespace App\Enums;

/**
 * The one status workflow every service request follows (FR-04):
 * Submitted > Under Review > Approved > Cash Payment > Completed.
 * Services with a ₱0.00 fee skip Cash Payment (Approved > Completed).
 */
enum RequestStatus: string
{
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case CashPayment = 'cash_payment';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under Review',
            self::Approved => 'Approved',
            self::CashPayment => 'Cash Payment',
            self::Completed => 'Completed',
        };
    }

    /**
     * The only status a request may move to next, given its fee.
     */
    public function next(bool $hasFee): ?self
    {
        return match ($this) {
            self::Submitted => self::UnderReview,
            self::UnderReview => self::Approved,
            self::Approved => $hasFee ? self::CashPayment : self::Completed,
            self::CashPayment => self::Completed,
            self::Completed => null,
        };
    }

    /**
     * The steps shown in the status tracker; a ₱0.00 service has no Cash Payment step.
     *
     * @return list<self>
     */
    public static function steps(bool $hasFee): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $status): bool => $hasFee || $status !== self::CashPayment,
        ));
    }

    /**
     * A short, plain-language line on what happens next, for the resident's tracker.
     */
    public function residentInstruction(): string
    {
        return match ($this) {
            self::Submitted => 'Your request was received. Barangay staff will review it and your documents.',
            self::UnderReview => 'Barangay staff are checking your request and documents.',
            self::Approved => 'Your request is approved. Staff are preparing your document.',
            self::CashPayment => 'Ready for fulfillment.',
            self::Completed => 'This request is completed.',
        };
    }
}
