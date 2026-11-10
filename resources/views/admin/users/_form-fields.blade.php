@php($user ??= null)

<div class="mb-3">
    <label for="name" class="form-label small">Full name</label>
    <input type="text" id="name" name="name" value="{{ old('name', $user?->name) }}" class="form-control @error('name') is-invalid @enderror" required>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="username" class="form-label small">Username</label>
    <input type="text" id="username" name="username" value="{{ old('username', $user?->username) }}" class="form-control @error('username') is-invalid @enderror" required>
    <div class="form-text">Letters, numbers, dashes and underscores only.</div>
    @error('username')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="position" class="form-label small">Position <span class="text-secondary">(optional)</span></label>
    <input type="text" id="position" name="position" value="{{ old('position', $user?->position) }}" class="form-control @error('position') is-invalid @enderror" placeholder="e.g. Secretary, Front Desk">
    @error('position')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="role" class="form-label small">Role</label>
    <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" required>
        @foreach ($roles as $role)
            <option value="{{ $role->value }}" @selected(old('role', $user?->role->value ?? 'staff') === $role->value)>{{ $role->label() }}</option>
        @endforeach
    </select>
    @error('role')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
