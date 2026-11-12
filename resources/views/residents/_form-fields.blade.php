{{-- Resident fields: Full Name, Address, Purok, Contact Number, and an optional Valid ID (Screen 8). Expects $bag, $puroks, optional $resident. --}}
@php($resident ??= null)

<div class="mb-3">
    <label for="{{ $bag }}-full-name" class="form-label">Full Name</label>
    <input type="text" id="{{ $bag }}-full-name" name="full_name" value="{{ old('full_name', $resident?->full_name) }}"
           class="form-control @error('full_name', $bag) is-invalid @enderror" placeholder="Last name, First name, M.I." required>
    @error('full_name', $bag)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="{{ $bag }}-address" class="form-label">Address</label>
    <input type="text" id="{{ $bag }}-address" name="address" value="{{ old('address', $resident?->address) }}"
           class="form-control @error('address', $bag) is-invalid @enderror" placeholder="House no., Street" required>
    @error('address', $bag)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-6">
        <label for="{{ $bag }}-purok" class="form-label">Purok</label>
        <select id="{{ $bag }}-purok" name="purok" class="form-select @error('purok', $bag) is-invalid @enderror" required>
            <option value="">Select purok</option>
            @foreach ($puroks as $purok)
                <option value="{{ $purok }}" @selected(old('purok', $resident?->purok) === $purok)>{{ $purok }}</option>
            @endforeach
        </select>
        @error('purok', $bag)
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-sm-6">
        <label for="{{ $bag }}-contact" class="form-label">Contact Number</label>
        <input type="text" id="{{ $bag }}-contact" name="contact_number" value="{{ old('contact_number', $resident?->contact_number) }}"
               class="form-control @error('contact_number', $bag) is-invalid @enderror" placeholder="09XX XXX XXXX" inputmode="tel">
        @error('contact_number', $bag)
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3">
    <label for="{{ $bag }}-valid-id" class="form-label">Valid ID <span class="text-secondary">(optional)</span></label>
    <input type="file" id="{{ $bag }}-valid-id" name="valid_id" accept=".jpg,.jpeg,.png,.pdf"
           class="form-control @error('valid_id', $bag) is-invalid @enderror">
    <div class="form-text">JPG, PNG, or PDF only. Maximum 5 MB.</div>
    @error('valid_id', $bag)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
