<?php

namespace App\Actions;

use App\Enums\LogEntryType;
use App\Enums\RequestStatus;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\User;
use App\Support\YearlyNumber;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Submit a request with its required documents (FR-02, FR-10): assigns the reference number, sets
 * Submitted, copies the service fee, stores the files privately, and writes the first log entry.
 * Used by the resident form, the staff-assisted form, and the demo seeder.
 */
class SubmitServiceRequestAction
{
    /**
     * @param  array{resident_id: int, service_type_id: int, purpose: string, received_at?: string}  $attributes
     * @param  array<int, UploadedFile>  $files  keyed by document requirement id
     */
    public function handle(array $attributes, User $submittedBy, array $files): ServiceRequest
    {
        return DB::transaction(function () use ($attributes, $submittedBy, $files): ServiceRequest {
            $serviceType = ServiceType::with('documentRequirements')->findOrFail($attributes['service_type_id']);

            $serviceRequest = ServiceRequest::create([
                'reference_no' => YearlyNumber::next(ServiceRequest::class, 'reference_no', 'SR'),
                'resident_id' => $attributes['resident_id'],
                'service_type_id' => $serviceType->id,
                'purpose' => $attributes['purpose'],
                'status' => RequestStatus::Submitted,
                'fee_amount' => $serviceType->fee,
                'received_at' => $attributes['received_at'] ?? today()->toDateString(),
                'created_by' => $submittedBy->id,
            ]);

            foreach ($serviceType->documentRequirements as $requirement) {
                $file = $files[$requirement->id] ?? null;

                if ($file) {
                    $serviceRequest->documents()->create([
                        'document_requirement_id' => $requirement->id,
                        'document_name' => $requirement->document_name,
                        'file_path' => $file->store("request-documents/{$serviceRequest->reference_no}"),
                        'original_name' => $file->getClientOriginalName(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            $serviceRequest->logs()->create([
                'user_id' => $submittedBy->id,
                'entry_type' => LogEntryType::Status,
                'new_status' => RequestStatus::Submitted,
            ]);

            return $serviceRequest;
        });
    }
}
