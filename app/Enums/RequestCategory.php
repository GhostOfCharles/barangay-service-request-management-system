<?php

namespace App\Enums;

/**
 * The three service categories handled by the single request workflow (FR-02).
 */
enum RequestCategory: string
{
    case CertificateClearance = 'certificate_clearance';
    case Complaint = 'complaint';
    case AssistanceReferral = 'assistance_referral';

    /**
     * Full label used on forms and reports.
     */
    public function label(): string
    {
        return match ($this) {
            self::CertificateClearance => 'Certificate / Clearance',
            self::Complaint => 'Complaint',
            self::AssistanceReferral => 'Community Assistance / Referral',
        };
    }

    /**
     * Short label used in tables, as in the wireframes.
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::CertificateClearance => 'Certificate',
            self::Complaint => 'Complaint',
            self::AssistanceReferral => 'Assistance',
        };
    }

    /**
     * Bar color used on the reports board.
     */
    public function color(): string
    {
        return match ($this) {
            self::CertificateClearance => '#a78bfa',
            self::Complaint => '#f2607a',
            self::AssistanceReferral => '#2e7d32',
        };
    }
}
