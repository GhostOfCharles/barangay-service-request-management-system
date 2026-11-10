<?php

namespace Database\Seeders;

use App\Enums\DocumentStatus;
use App\Enums\RequestCategory;
use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Fictional demo records based on the planning document wireframes. Not real resident data.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (Resident::query()->exists()) {
            return;
        }

        $staff = User::firstOrCreate(
            ['username' => 'staff'],
            [
                'name' => 'Sec. Cruz',
                'role' => UserRole::Staff,
                'position' => 'Secretary',
                'is_active' => true,
                'must_change_password' => false,
                'password' => 'password',
            ],
        );

        $residents = collect([
            ['Maria Santos', '12 Sampaguita St., Purok 3', '09171234567', 'Purok 3'],
            ['Juan dela Cruz', '7 Narra Ave., Purok 1', '09281234567', 'Purok 1'],
            ['Rosario Reyes', '34 Rosal St., Purok 2', '09351234567', 'Purok 2'],
            ['Eduardo Bautista', '2 Mabini St., Purok 4', '09191234567', 'Purok 4'],
            ['Lourdes Mendoza', '55 Rizal St., Purok 1', '09261234567', 'Purok 1'],
            ['Pedro Villanueva', '18 Bonifacio St., Purok 5', '09471234567', 'Purok 5'],
            ['Cynthia Flores', '9 Aguinaldo Ave., Purok 3', '09551234567', 'Purok 3'],
        ])->mapWithKeys(fn (array $row) => [
            $row[0] => Resident::create([
                'name' => $row[0],
                'address' => $row[1],
                'contact_number' => $row[2],
                'purok' => $row[3],
            ]),
        ]);

        $requests = [
            ['Maria Santos', RequestCategory::CertificateClearance, 'Barangay clearance for employment requirements.', 6, RequestStatus::Completed],
            ['Rosario Reyes', RequestCategory::Complaint, 'Noise complaint against neighbor at 36 Rosal St. during late hours.', 4, RequestStatus::InProgress],
            ['Juan dela Cruz', RequestCategory::AssistanceReferral, 'Request for referral to DSWD for financial assistance.', 3, RequestStatus::Pending],
            ['Lourdes Mendoza', RequestCategory::CertificateClearance, 'Certificate of residency for school enrollment.', 2, RequestStatus::Pending],
            ['Eduardo Bautista', RequestCategory::Complaint, 'Illegal dumping of garbage near Purok 4 creek.', 1, RequestStatus::InProgress],
            ['Cynthia Flores', RequestCategory::AssistanceReferral, 'Assistance request for PWD ID processing.', 0, RequestStatus::Pending],
        ];

        foreach ($requests as [$residentName, $category, $description, $daysAgo, $status]) {
            $serviceRequest = ServiceRequest::record([
                'resident_id' => $residents[$residentName]->id,
                'category' => $category->value,
                'description' => $description,
                'document_status' => DocumentStatus::Complete->value,
                'date_received' => Carbon::today()->subDays($daysAgo)->toDateString(),
            ], $staff);

            if ($status !== RequestStatus::Pending) {
                $serviceRequest->changeStatus(RequestStatus::InProgress, $staff, 'Forwarded to the barangay secretary for review.');
            }

            if ($status === RequestStatus::Completed) {
                $serviceRequest->changeStatus(RequestStatus::Completed, $staff, 'Document signed and released to the resident.');
            }
        }
    }
}
