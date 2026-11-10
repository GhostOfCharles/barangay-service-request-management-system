{{-- Shared resident fields: Name, Address, Contact, Purok (Screen 4). Expects $bag and optional $resident. --}}
@php($resident ??= null)

<div class="mb-3">
    <label for="{{ $bag }}-name" class="form-label small">Name</label>
    <input type="text" id="{{ $bag }}-name" name="name" value="{{ old('name', $resident?->name) }}"
           class="form-control @error('name', $bag) is-invalid @enderror" placeholder="Enter name" required>
    @error('name', $bag)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="{{ $bag }}-address" class="form-label small">Address</label>
    <input type="text" id="{{ $bag }}-address" name="address" value="{{ old('address', $resident?->address) }}"
           class="form-control @error('address', $bag) is-invalid @enderror" placeholder="Enter address" required>
    @error('address', $bag)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="{{ $bag }}-contact" class="form-label small">Contact <span class="text-secondary">(optional)</span></label>
    <input type="text" id="{{ $bag }}-contact" name="contact_number" value="{{ old('contact_number', $resident?->contact_number) }}"
           class="form-control @error('contact_number', $bag) is-invalid @enderror" placeholder="Enter contact">
    @error('contact_number', $bag)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="{{ $bag }}-purok" class="form-label small">Purok</label>
    <input type="text" id="{{ $bag }}-purok" name="purok" value="{{ old('purok', $resident?->purok) }}"
           class="form-control @error('purok', $bag) is-invalid @enderror" placeholder="Enter purok" required>
    @error('purok', $bag)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
