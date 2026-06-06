@extends('layouts.admin')

@section('title', 'Edit User')

@section('content')
<div class="card mb-3">
  <div class="card-header bg-light d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Edit User: {{ $user->name }}</h5>
    <a class="btn btn-falcon-default btn-sm" href="{{ route('users.index') }}">
      <span class="fas fa-chevron-left me-1" data-fa-transform="shrink-4"></span> Back
    </a>
  </div>
  
  <div class="card-body">
    <form action="{{ route('users.update', $user->id) }}" method="POST">
      @csrf
      @method('PUT')

      <div class="row g-3">
        <!-- Marquee selector for Super Admins -->
        @if(auth()->user()->isSuperAdmin())
          <div class="col-12">
            <label class="form-label" for="marquee_id">Select Marquee Tenant</label>
            <select class="form-select @error('marquee_id') is-invalid @enderror" id="marquee_id" name="marquee_id">
              <option value="">SaaS Administrator (No Tenant Assignment)</option>
              @foreach($marquees as $marquee)
                <option value="{{ $marquee->id }}" {{ old('marquee_id', $user->marquee_id) == $marquee->id ? 'selected' : '' }}>
                  {{ $marquee->name }}
                </option>
              @endforeach
            </select>
            @error('marquee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        @endif

        <!-- Name -->
        <div class="col-md-6">
          <label class="form-label" for="name">Full Name *</label>
          <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required />
          @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <!-- Email -->
        <div class="col-md-6">
          <label class="form-label" for="email">Email Address *</label>
          <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required />
          @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <!-- Phone -->
        <div class="col-md-6">
          <label class="form-label" for="phone">Phone Number</label>
          <input class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" type="text" value="{{ old('phone', $user->phone) }}" />
          @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <!-- Password -->
        <div class="col-md-6">
          <label class="form-label" for="password">Password (Leave blank to keep current)</label>
          <div class="input-group">
            <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" />
            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password', this)">
              <span class="fas fa-eye"></span>
            </button>
            @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
          </div>
        </div>

        <!-- Role -->
        <div class="col-md-4">
          <label class="form-label" for="role_id">Role *</label>
          <select class="form-select @error('role_id') is-invalid @enderror" id="role_id" name="role_id" required>
            @foreach($roles as $role)
              <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                {{ $role->label }}
              </option>
            @endforeach
          </select>
          @error('role_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <!-- Branch Location -->
        <div class="col-md-4">
          <label class="form-label" for="branch_id">Assigned Branch</label>
          <select class="form-select @error('branch_id') is-invalid @enderror" id="branch_id" name="branch_id">
            <option value="">All Branches / Company-wide</option>
            @foreach($branches as $branch)
              <option value="{{ $branch->id }}" {{ old('branch_id', $user->branch_id) == $branch->id ? 'selected' : '' }}>
                {{ $branch->name }} @if(auth()->user()->isSuperAdmin()) ({{ $branch->marquee->name ?? '' }}) @endif
              </option>
            @endforeach
          </select>
          @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <!-- Status -->
        <div class="col-md-4">
          <label class="form-label" for="status">Account Status *</label>
          <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
            <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status', $user->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
          </select>
          @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
      </div>

      <div class="mt-4 d-flex justify-content-end gap-2">
        <a class="btn btn-falcon-default btn-sm" href="{{ route('users.index') }}">Cancel</a>
        <button class="btn btn-primary btn-sm" type="submit">Update User</button>
      </div>
    </form>
  </div>
</div>

@section('scripts')
<script>
  function togglePassword(id, btn) {
    const input = document.getElementById(id);
    const icon = btn.querySelector('span');
    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.remove('fa-eye');
      icon.classList.add('fa-eye-slash');
    } else {
      input.type = 'password';
      icon.classList.remove('fa-eye-slash');
      icon.classList.add('fa-eye');
    }
  }

  function setupPhoneMask(inputSelector) {
    const input = document.querySelector(inputSelector);
    if (!input) return;
    
    const prefix = "+92";
    const maxDigits = 10;
    const placeholder = prefix + "-".repeat(maxDigits);

    if (!input.value || input.value.trim() === "" || input.value === "+92") {
        input.value = placeholder;
    } else {
        let val = input.value;
        let clean = val.startsWith(prefix) ? val.substring(prefix.length) : val;
        let digits = clean.replace(/\D/g, '').substring(0, maxDigits);
        let dashes = "-".repeat(maxDigits - digits.length);
        input.value = prefix + digits + dashes;
    }

    input.addEventListener('input', function() {
        let val = input.value;
        let clean = val.startsWith(prefix) ? val.substring(prefix.length) : val;
        let digits = clean.replace(/\D/g, '').substring(0, maxDigits);
        let dashes = "-".repeat(maxDigits - digits.length);
        input.value = prefix + digits + dashes;

        let pos = prefix.length + digits.length;
        input.setSelectionRange(pos, pos);
    });

    input.addEventListener('focus', function() {
        if (input.value === placeholder) {
            input.setSelectionRange(prefix.length, prefix.length);
        }
    });

    input.addEventListener('click', function() {
        let val = input.value;
        let clean = val.startsWith(prefix) ? val.substring(prefix.length) : val;
        let digits = clean.replace(/\D/g, '').substring(0, maxDigits);
        let pos = prefix.length + digits.length;
        input.setSelectionRange(pos, pos);
    });

    if (input.form) {
        input.form.addEventListener('submit', function() {
            input.value = input.value.replace(/-+$/, '');
        });
    }
  }

  document.addEventListener('DOMContentLoaded', function() {
      setupPhoneMask('#phone');
  });
</script>
@endsection
@endsection
