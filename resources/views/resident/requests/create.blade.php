@extends('layouts.app')

@section('title', 'New Service Request')

@section('content')
    <div class="mb-3">
        <h1 class="page-title">New Service Request</h1>
        <p class="page-subtitle mb-0">Select a service type. The fee and required documents update automatically.</p>
    </div>

    <form method="POST" action="{{ route('resident.requests.store') }}" enctype="multipart/form-data" novalidate data-submission-form>
        @csrf

        <x-submission-fields :options="$serviceTypeOptions" bag="submitRequest" :columns="true">
            <x-slot:after>
                <p class="small text-secondary">Reference number is generated when you submit. Your status appears in My Requests.</p>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <button type="submit" class="btn btn-success" data-submit>Submit Request</button>
                    <a href="{{ route('resident.dashboard') }}" class="btn btn-outline-secondary bg-white">Cancel</a>
                </div>
                <p class="small text-secondary mt-2 mb-0" data-submit-hint aria-live="polite"></p>
            </x-slot:after>
        </x-submission-fields>
    </form>
@endsection
