{{-- Shared request fields: category, description, document check, date received (Screens 5–6). Expects $bag and optional $serviceRequest. --}}
@php($serviceRequest ??= null)

<div class="mb-3">
    <label for="{{ $bag }}-category" class="form-label small">Category</label>
    <select id="{{ $bag }}-category" name="category" class="form-select @error('category', $bag) is-invalid @enderror" required>
        <option value="">— Select category —</option>
        @foreach ($categories as $category)
            <option value="{{ $category->value }}" @selected(old('category', $serviceRequest?->category->value) === $category->value)>{{ $category->label() }}</option>
        @endforeach
    </select>
    @error('category', $bag)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="{{ $bag }}-description" class="form-label small">Description</label>
    <textarea id="{{ $bag }}-description" name="description" rows="3"
              class="form-control @error('description', $bag) is-invalid @enderror"
              placeholder="Briefly describe the request..." required>{{ old('description', $serviceRequest?->description) }}</textarea>
    @error('description', $bag)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-6">
        <label for="{{ $bag }}-document_status" class="form-label small">Document Check</label>
        <select id="{{ $bag }}-document_status" name="document_status" class="form-select @error('document_status', $bag) is-invalid @enderror" required>
            <option value="">— Select —</option>
            @foreach ($documentStatuses as $documentStatus)
                <option value="{{ $documentStatus->value }}" @selected(old('document_status', $serviceRequest?->document_status->value) === $documentStatus->value)>{{ $documentStatus->label() }}</option>
            @endforeach
        </select>
        @error('document_status', $bag)
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-sm-6">
        <label for="{{ $bag }}-date_received" class="form-label small">Date Received</label>
        <input type="date" id="{{ $bag }}-date_received" name="date_received" max="{{ today()->toDateString() }}"
               value="{{ old('date_received', $serviceRequest?->date_received->toDateString() ?? today()->toDateString()) }}"
               class="form-control @error('date_received', $bag) is-invalid @enderror" required>
        @error('date_received', $bag)
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
