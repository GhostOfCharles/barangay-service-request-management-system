<?php

namespace App\Http\Requests;

/**
 * Add New Service Request (Staff-Assisted, Screen 11): the same checklist rules as the resident form,
 * plus the walk-in resident and the date the request was received at the office.
 */
class StaffSubmitServiceRequest extends SubmitServiceRequest
{
    /** @var string */
    protected $errorBag = 'createRequest';

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'resident_id' => ['required', 'integer', 'exists:residents,id'],
            'received_at' => ['required', 'date', 'before_or_equal:today'],
            ...parent::rules(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function submissionAttributes(): array
    {
        return $this->safe()->only(['resident_id', 'service_type_id', 'purpose', 'received_at']);
    }
}
