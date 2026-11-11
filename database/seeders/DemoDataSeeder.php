<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Fictional demo records based on the planning document wireframes. Not real resident data.
 * Uploaded files are generated sample PDFs on the private disk.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (Resident::query()->exists()) {
            return;
        }

        $staff = User::firstOrCreate(['username' => 'staff'], [
            'name' => 'Sec. Cruz',
            'role' => UserRole::Staff,
            'status' => AccountStatus::Active,
            'position' => 'Secretary',
            'password' => 'password',
        ]);

        $demoAdmin = User::firstOrCreate(['username' => 'demoadmin'], [
            'name' => 'Demo Administrator',
            'role' => UserRole::Administrator,
            'status' => AccountStatus::Active,
            'position' => 'Administrator',
            'password' => 'password',
        ]);

        // Verified residents; Cynthia Flores also has a resident portal account.
        $residents = collect([
            ['Maria Santos', '12 Sampaguita St.', 'Purok 3', '09171234567'],
            ['Juan dela Cruz', '7 Mabini St.', 'Purok 2', '09182223344'],
            ['Rosario Reyes', '9 Del Pilar St.', 'Purok 4', '09215556677'],
            ['Eduardo Bautista', '21 Luna St.', 'Purok 1', '09204445566'],
            ['Lourdes Mendoza', '3 Bonifacio Ave.', 'Purok 3', '09193334455'],
            ['Pedro Villanueva', '18 Rizal St.', 'Purok 5', '09471234567'],
            ['Cynthia Flores', '12 Rizal St.', 'Purok 1', '09171112233'],
        ])->mapWithKeys(fn (array $row) => [$row[0] => Resident::create([
            'full_name' => $row[0],
            'address' => $row[1],
            'purok' => $row[2],
            'contact_number' => $row[3],
            'verification_status' => VerificationStatus::Verified,
            'verified_by' => $staff->id,
            'verified_at' => now()->subDays(20),
        ])]);

        $cynthiaAccount = User::create([
            'name' => 'Cynthia Flores',
            'username' => 'resident',
            'role' => UserRole::Resident,
            'status' => AccountStatus::Active,
            'password' => 'password',
        ]);
        $residents['Cynthia Flores']->update([
            'user_id' => $cynthiaAccount->id,
            'valid_id_path' => $this->samplePdf('valid-ids', 'Sample valid ID: Cynthia Flores'),
            'privacy_consent_at' => now()->subDays(25),
        ]);

        // Self-registrations still waiting for residency verification.
        foreach ([['Ramon Aquino', '4 Kalaw St.', 'Purok 6', '09281119988', 'ramon'], ['Liza Ramos', '15 Gomez St.', 'Purok 2', '09351234499', 'liza']] as [$name, $address, $purok, $contact, $username]) {
            $account = User::create([
                'name' => $name,
                'username' => $username,
                'role' => UserRole::Resident,
                'status' => AccountStatus::Pending,
                'password' => 'password',
            ]);

            Resident::create([
                'user_id' => $account->id,
                'full_name' => $name,
                'address' => $address,
                'purok' => $purok,
                'contact_number' => $contact,
                'valid_id_path' => $this->samplePdf('valid-ids', "Sample valid ID: {$name}"),
                'verification_status' => VerificationStatus::Pending,
                'privacy_consent_at' => now()->subDay(),
            ]);
        }

        // Requests at every step of the workflow, oldest first.
        $requests = [
            ['Maria Santos', 'Business Clearance', 'Renewal of business permit for a sari-sari store.', 8, RequestStatus::Completed, $staff],
            ['Rosario Reyes', 'Certificate of Residency', 'School enrollment requirement.', 6, RequestStatus::Completed, $staff],
            ['Cynthia Flores', 'Certificate of Residency', 'Bank account opening.', 5, RequestStatus::Completed, $cynthiaAccount],
            ['Juan dela Cruz', 'Medical Assistance', 'Assistance for hospital bills after surgery.', 4, RequestStatus::Approved, $staff],
            ['Lourdes Mendoza', 'Barangay ID Assistance', 'Replacement of a lost barangay ID.', 3, RequestStatus::Submitted, $staff],
            ['Eduardo Bautista', 'Complaint / Blotter', 'Illegal dumping of garbage near the Purok 1 creek.', 2, RequestStatus::UnderReview, $staff],
            ['Cynthia Flores', 'Barangay Clearance', 'Employment requirement.', 1, RequestStatus::CashPayment, $cynthiaAccount],
        ];

        $serviceTypes = ServiceType::with('documentRequirements')->get()->keyBy('name');
        $realNow = now()->toImmutable();
        $created = [];

        foreach ($requests as [$residentName, $serviceName, $purpose, $daysAgo, $finalStatus, $submittedBy]) {
            Carbon::setTestNow($realNow->subDays($daysAgo)->setTime(9, 5));

            $serviceType = $serviceTypes[$serviceName];
            $files = $serviceType->documentRequirements->mapWithKeys(fn ($requirement) => [
                $requirement->id => $this->sampleUpload("Sample {$requirement->document_name}: {$residentName}"),
            ])->all();

            $serviceRequest = ServiceRequest::submit([
                'resident_id' => $residents[$residentName]->id,
                'service_type_id' => $serviceType->id,
                'purpose' => $purpose,
                'received_at' => today()->toDateString(),
            ], $submittedBy, $files);

            while ($serviceRequest->status !== $finalStatus) {
                Carbon::setTestNow(now()->addHours(2));
                $next = $serviceRequest->nextStatus();
                $serviceRequest->moveTo($next, $next === RequestStatus::Completed ? $demoAdmin : $staff);

                if ($next === RequestStatus::Approved && $serviceRequest->hasFee()) {
                    $serviceRequest->addNote('Documents complete. For the captain\'s signature.', $staff);
                }
            }

            $created[$residentName.'|'.$serviceName] = $serviceRequest;
        }

        Carbon::setTestNow();

        // Support tickets from the resident portal account.
        $missingDocument = Ticket::open(
            $residents['Cynthia Flores'],
            $cynthiaAccount,
            'Missing document',
            'I uploaded my ID but the request still says it needs a proof of residency. What should I attach?',
            $created['Cynthia Flores|Barangay Clearance']->id,
        );
        $missingDocument->reply($staff, 'A barangay certificate or a utility bill with your address works. Please attach it to a new ticket reply or your request.');

        Ticket::open($residents['Cynthia Flores'], $cynthiaAccount, 'Fee question', 'Is the clearance fee paid before or after I receive the document?')
            ->close();

        Ticket::open($residents['Cynthia Flores'], $cynthiaAccount, 'Wrong address', 'My address on file should be 12 Rizal St., not 21 Rizal St.');
    }

    /**
     * Store a small sample PDF on the private disk and return its path.
     */
    private function samplePdf(string $folder, string $text): string
    {
        $path = $folder.'/sample-'.uniqid().'.pdf';
        Storage::disk('local')->put($path, self::pdfWithText($text));

        return $path;
    }

    private function sampleUpload(string $text): UploadedFile
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'demo');
        file_put_contents($temporaryPath, self::pdfWithText($text));

        return new UploadedFile($temporaryPath, 'sample-document.pdf', 'application/pdf', null, true);
    }

    /**
     * A one-page PDF showing a line of text, so "View" opens something readable.
     */
    private static function pdfWithText(string $text): string
    {
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        $stream = "BT /F1 18 Tf 60 720 Td ({$text}) Tj ET\nBT /F1 11 Tf 60 690 Td (Demo file generated by DemoDataSeeder. Not a real document.) Tj ET";

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= 'xref
0 '.(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= str_pad((string) $offset, 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }

        return $pdf.'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }
}
