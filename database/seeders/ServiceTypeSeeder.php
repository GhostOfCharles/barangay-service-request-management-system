<?php

namespace Database\Seeders;

use App\Enums\RequestCategory;
use App\Models\ServiceType;
use Illuminate\Database\Seeder;

/**
 * The starting service types, fees and checklists from the planning document (Screen 13).
 * Only the Barangay Clearance checklist is named in the document; the other checklists are
 * reasonable starting points that the Administrator can edit on the Service Types screen.
 */
class ServiceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $serviceTypes = [
            ['Barangay Clearance', RequestCategory::CertificateClearance, '50.00', ['Valid ID (front)', 'Proof of Residency', 'Community Tax Certificate']],
            ['Certificate of Residency', RequestCategory::CertificateClearance, '30.00', ['Valid ID (front)', 'Proof of Residency']],
            ['Business Clearance', RequestCategory::CertificateClearance, '100.00', ['Valid ID (front)', 'DTI or SEC Registration', 'Proof of Business Address', 'Community Tax Certificate']],
            ['Medical Assistance', RequestCategory::AssistanceReferral, '0.00', ['Valid ID (front)', 'Medical Certificate or Abstract', 'Hospital Bill or Prescription']],
            ['Complaint / Blotter', RequestCategory::Complaint, '0.00', ['Valid ID (front)']],
            ['Barangay ID Assistance', RequestCategory::AssistanceReferral, '0.00', ['Valid ID (front)', 'Proof of Residency']],
        ];

        foreach ($serviceTypes as [$name, $category, $fee, $documents]) {
            $serviceType = ServiceType::firstOrCreate(
                ['name' => $name],
                ['category' => $category, 'fee' => $fee, 'is_active' => true],
            );

            if ($serviceType->wasRecentlyCreated) {
                foreach ($documents as $document) {
                    $serviceType->documentRequirements()->create(['document_name' => $document, 'is_required' => true]);
                }
            }
        }
    }
}
