{{--
    Dynamic Submission Form fields (FR-10, Screens 4 and 11). Place inside a <form data-submission-form
    enctype="multipart/form-data"> that also has a [data-submit] button and an optional [data-submit-hint].
--}}
@props(['options', 'bag', 'columns' => false])

@php
    $errorBag = $errors->getBag($bag);
    $documentErrors = collect($errorBag->getMessages())
        ->filter(fn ($messages, $key) => str_starts_with($key, 'documents.'))
        ->mapWithKeys(fn ($messages, $key) => [(int) substr($key, strlen('documents.')) => $messages[0]]);
@endphp

<script type="application/json" data-service-types>@json($options)</script>
<script type="application/json" data-document-errors>@json($documentErrors)</script>

<div class="row g-4">
    <div class="{{ $columns ? 'col-lg-7' : 'col-12' }}">
        {{ $before ?? '' }}

        <div class="mb-3">
            <label for="{{ $bag }}-service-type" class="form-label">Service Type</label>
            <select id="{{ $bag }}-service-type" name="service_type_id" data-service-select required
                    class="form-select @if ($errorBag->has('service_type_id')) is-invalid @endif">
                <option value="">Select a service type</option>
                @foreach ($options as $option)
                    <option value="{{ $option['id'] }}" @selected((string) old('service_type_id') === (string) $option['id'])>{{ $option['name'] }}</option>
                @endforeach
            </select>
            @if ($errorBag->has('service_type_id'))
                <div class="invalid-feedback">{{ $errorBag->first('service_type_id') }}</div>
            @endif
        </div>

        <div class="row g-3 mb-3">
            <div class="col-sm-6">
                <label for="{{ $bag }}-fee" class="form-label">Fee (paid in cash upon receipt)</label>
                <input type="text" id="{{ $bag }}-fee" class="form-control" data-fee readonly tabindex="-1" placeholder="₱0.00">
            </div>
            <div class="col-sm-6">
                <label for="{{ $bag }}-category" class="form-label">Category</label>
                <input type="text" id="{{ $bag }}-category" class="form-control" data-category readonly tabindex="-1">
            </div>
        </div>

        <div class="mb-3">
            <label for="{{ $bag }}-purpose" class="form-label">Purpose / Details</label>
            <textarea id="{{ $bag }}-purpose" name="purpose" rows="3" required
                      class="form-control @if ($errorBag->has('purpose')) is-invalid @endif"
                      placeholder="e.g., Employment requirement">{{ old('purpose') }}</textarea>
            @if ($errorBag->has('purpose'))
                <div class="invalid-feedback">{{ $errorBag->first('purpose') }}</div>
            @endif
        </div>

        {{ $after ?? '' }}
    </div>

    <div class="{{ $columns ? 'col-lg-5' : 'col-12' }}">
        <div class="panel p-3">
            <h2 class="h6 fw-bold mb-3" data-checklist-title>Required Documents</h2>
            @if ($documentErrors->isNotEmpty())
                <div class="alert alert-warning small py-2">
                    Files are not kept after an error. Please attach the documents again.
                </div>
            @endif
            <ul class="checklist" data-checklist aria-live="polite"></ul>
            <p class="form-text mb-0 mt-2">Accepted: JPG, PNG, PDF. Up to 5 MB per file. All required documents must be attached before you can submit.</p>
        </div>
    </div>
</div>
