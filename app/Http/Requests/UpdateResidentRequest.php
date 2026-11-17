<?php

namespace App\Http\Requests;

/**
 * Edit a resident record (resident detail page). Same rules as Add New Resident, with errors
 * in the "updateResident" bag so they show on the edit form.
 */
class UpdateResidentRequest extends StoreResidentRequest
{
    /** @var string */
    protected $errorBag = 'updateResident';
}
