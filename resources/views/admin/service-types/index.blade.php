@extends('layouts.app')

@section('title', 'Service Types, Fees and Checklists')

@php
    $form = $editing ?? ($creating ? new \App\Models\ServiceType(['fee' => '0.00', 'is_active' => true]) : null);
    $rows = old('documents', $editing
        ? $editing->documentRequirements->map(fn ($requirement) => ['id' => $requirement->id, 'name' => $requirement->document_name, 'required' => $requirement->is_required])->all()
        : [['id' => null, 'name' => '', 'required' => true]]);
@endphp

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="page-title">Service Types, Fees and Checklists</h1>
            <p class="page-subtitle mb-0">Administrator only</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.service-types.index', ['new' => 1]) }}" class="btn btn-success"><i class="bi bi-plus-lg"></i> Add Service Type</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="panel">
                <div class="table-responsive">
                    <table class="table table-app">
                        <thead>
                        <tr>
                            <th>Service Type</th>
                            <th class="d-none d-sm-table-cell">Category</th>
                            <th class="text-end">Fee</th>
                            <th class="text-end">Docs</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($serviceTypes as $serviceType)
                            <tr @class(['table-active' => $editing?->is($serviceType)])>
                                <td class="fw-medium">
                                    {{ $serviceType->name }}
                                    @unless ($serviceType->is_active)
                                        <span class="status-badge status-account-inactive ms-1">Inactive</span>
                                    @endunless
                                </td>
                                <td class="d-none d-sm-table-cell">{{ $serviceType->category->shortLabel() }}</td>
                                <td class="text-end"><x-peso :amount="$serviceType->fee" /></td>
                                <td class="text-end">{{ $serviceType->document_requirements_count }}</td>
                                <td class="text-end"><a href="{{ route('admin.service-types.index', ['edit' => $serviceType->id]) }}" class="small fw-semibold">Edit</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-4">No service types yet. Use Add Service Type to create one.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            @if ($form)
                <div class="panel p-3">
                    <h2 class="h6 fw-bold mb-3">{{ $editing ? 'Edit: '.$editing->name : 'New Service Type' }}</h2>

                    <form method="POST" action="{{ $editing ? route('admin.service-types.update', $editing) : route('admin.service-types.store') }}" novalidate>
                        @csrf
                        @if ($editing)
                            @method('PUT')
                        @endif

                        <div class="row g-3 mb-3">
                            <div class="col-sm-7">
                                <label for="service-name" class="form-label">Service Name</label>
                                <input type="text" id="service-name" name="name" value="{{ old('name', $form->name) }}" required
                                       class="form-control @error('name') is-invalid @enderror">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-sm-5">
                                <label for="service-fee" class="form-label">Fee (₱)</label>
                                <input type="number" id="service-fee" name="fee" value="{{ old('fee', $form->fee) }}" min="0" step="0.01" required inputmode="decimal"
                                       class="form-control @error('fee') is-invalid @enderror">
                                @error('fee')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="service-category" class="form-label">Category</label>
                            <select id="service-category" name="category" required class="form-select @error('category') is-invalid @enderror">
                                @foreach ($categories as $category)
                                    <option value="{{ $category->value }}" @selected(old('category', $form->category?->value) === $category->value)>{{ $category->label() }}</option>
                                @endforeach
                            </select>
                            @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Required Document Checklist: add or remove rows; untick "Required" for an optional document. --}}
                        <div class="mb-3" data-checklist-editor>
                            <div class="form-label">Required Document Checklist</div>
                            <ul class="checklist" data-checklist-rows>
                                @foreach ($rows as $index => $row)
                                    <li class="checklist-row" data-checklist-row>
                                        <input type="hidden" name="documents[{{ $index }}][id]" value="{{ $row['id'] ?? '' }}">
                                        <input type="hidden" name="documents[{{ $index }}][required]" value="0">
                                        <input type="checkbox" class="form-check-input mt-0" id="doc-required-{{ $index }}" name="documents[{{ $index }}][required]" value="1"
                                               @checked($row['required'] ?? true) title="Required">
                                        <label for="doc-required-{{ $index }}" class="visually-hidden">Required</label>
                                        <input type="text" name="documents[{{ $index }}][name]" value="{{ $row['name'] ?? '' }}"
                                               class="form-control form-control-sm" placeholder="Document name, e.g. Valid ID (front)" aria-label="Document name">
                                        <button type="button" class="btn btn-link btn-sm text-secondary text-decoration-none" data-remove-row>Remove</button>
                                    </li>
                                @endforeach
                            </ul>
                            <template data-checklist-template>
                                <li class="checklist-row" data-checklist-row>
                                    <input type="hidden" name="documents[__INDEX__][id]" value="">
                                    <input type="hidden" name="documents[__INDEX__][required]" value="0">
                                    <input type="checkbox" class="form-check-input mt-0" id="doc-required-__INDEX__" name="documents[__INDEX__][required]" value="1" checked title="Required">
                                    <label for="doc-required-__INDEX__" class="visually-hidden">Required</label>
                                    <input type="text" name="documents[__INDEX__][name]" value="" class="form-control form-control-sm"
                                           placeholder="Document name, e.g. Valid ID (front)" aria-label="Document name">
                                    <button type="button" class="btn btn-link btn-sm text-secondary text-decoration-none" data-remove-row>Remove</button>
                                </li>
                            </template>
                            <button type="button" class="btn btn-link px-0 mt-2 fw-semibold text-decoration-none" data-add-row><i class="bi bi-plus-lg"></i> Add required document</button>
                            <div class="form-text">The checkbox marks a document as required. Untick it for an optional document.</div>
                        </div>

                        <p class="small text-secondary">A fee of ₱0.00 skips the Cash Payment status.</p>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-success">{{ $editing ? 'Save Changes' : 'Add Service Type' }}</button>
                            @if ($editing)
                                <button type="submit" form="toggle-service-type" class="btn btn-outline-secondary bg-white">
                                    {{ $editing->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            @else
                                <a href="{{ route('admin.service-types.index') }}" class="btn btn-outline-secondary bg-white">Cancel</a>
                            @endif
                        </div>
                    </form>

                    @if ($editing)
                        <form method="POST" action="{{ route('admin.service-types.toggle', $editing) }}" id="toggle-service-type" class="d-none">@csrf</form>
                    @endif
                </div>
            @else
                <div class="panel p-4 text-secondary">
                    Choose <strong>Edit</strong> on a service type to change its fee and document checklist, or use <strong>Add Service Type</strong>.
                    Changes appear on the residents' submission form right away.
                </div>
            @endif
        </div>
    </div>
@endsection
