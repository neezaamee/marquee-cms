@extends('layouts.admin')

@section('title', 'Edit Employee: ' . $staff->name)

@section('content')
<div class="card mb-3">
  <div class="card-header bg-light d-flex justify-content-between align-items-center">
    <h5 class="mb-0">
      <span class="fas fa-user-edit me-2 text-primary"></span>Edit Employee
      <small class="text-muted ms-2 fs-10 font-monospace">{{ $staff->employee_id }}</small>
    </h5>
    <a class="btn btn-falcon-default btn-sm" href="{{ route('staff.index') }}">
      <span class="fas fa-chevron-left me-1" data-fa-transform="shrink-4"></span> Back to Staff
    </a>
  </div>

  <div class="card-body">
    <form action="{{ route('staff.update', $staff->id) }}" method="POST" enctype="multipart/form-data">
      @csrf
      @method('PUT')

      {{-- ── Section 1: Basic Info ── --}}
      <div class="row navbar-vertical-label-wrapper mb-3 mt-2">
        <div class="col-auto navbar-vertical-label text-primary fw-bold">
          <span class="fas fa-user me-1"></span> Basic Information
        </div>
        <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
      </div>

      <div class="row g-3">
        {{-- Profile Photo --}}
        <div class="col-12">
          <label class="form-label" for="photo">Profile Photo</label>
          @if($staff->photo)
            <div class="mb-2">
              <img src="{{ asset('storage/' . $staff->photo) }}" alt="{{ $staff->name }}" class="rounded" height="80" style="object-fit:cover;">
              <small class="text-muted d-block mt-1">Upload a new image to replace the current photo.</small>
            </div>
          @endif
          <input class="form-control @error('photo') is-invalid @enderror" id="photo" name="photo" type="file" accept="image/jpeg,image/png" />
          @error('photo') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Name --}}
        <div class="col-md-6">
          <label class="form-label" for="name">Full Name *</label>
          <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" type="text"
                 value="{{ old('name', $staff->name) }}" required />
          @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- CNIC --}}
        <div class="col-md-6">
          <label class="form-label" for="cnic">CNIC Number *</label>
          <input class="form-control @error('cnic') is-invalid @enderror" id="cnic" name="cnic" type="text"
                 value="{{ old('cnic', $staff->cnic) }}" required maxlength="15" />
          @error('cnic') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Mobile --}}
        <div class="col-md-6">
          <label class="form-label" for="mobile_number">Mobile Number *</label>
          <input class="form-control @error('mobile_number') is-invalid @enderror" id="mobile_number" name="mobile_number"
                 type="text" value="{{ old('mobile_number', $staff->mobile_number) }}" required />
          @error('mobile_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Branch --}}
        <div class="col-md-6">
          <label class="form-label" for="branch_id">Branch *</label>
          <select class="form-select @error('branch_id') is-invalid @enderror" id="branch_id" name="branch_id" required>
            <option value="">Select Branch...</option>
            @foreach($branches as $branch)
              <option value="{{ $branch->id }}" {{ old('branch_id', $staff->branch_id) == $branch->id ? 'selected' : '' }}>
                {{ $branch->name }}
              </option>
            @endforeach
          </select>
          @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
      </div>

      {{-- ── Section 2: Job Info ── --}}
      <div class="row navbar-vertical-label-wrapper mb-3 mt-4">
        <div class="col-auto navbar-vertical-label text-primary fw-bold">
          <span class="fas fa-briefcase me-1"></span> Job Information
        </div>
        <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
      </div>

      <div class="row g-3">
        {{-- Designation --}}
        <div class="col-md-6">
          <label class="form-label" for="designation">Designation *</label>
          <select class="form-select @error('designation') is-invalid @enderror" id="designation" name="designation" required>
            <option value="">Select Designation...</option>
            @foreach($designations as $d)
              <option value="{{ $d }}" {{ old('designation', $staff->designation) === $d ? 'selected' : '' }}>{{ $d }}</option>
            @endforeach
          </select>
          @error('designation') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Employment Type --}}
        <div class="col-md-6">
          <label class="form-label" for="employment_type">Employment Type *</label>
          <select class="form-select @error('employment_type') is-invalid @enderror" id="employment_type" name="employment_type" required>
            @foreach(\App\Models\Employee::EMPLOYMENT_TYPES as $type)
              <option value="{{ $type }}" {{ old('employment_type', $staff->employment_type) === $type ? 'selected' : '' }}>{{ $type }}</option>
            @endforeach
          </select>
          @error('employment_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Joining Date --}}
        <div class="col-md-6">
          <label class="form-label" for="joining_date">Joining Date *</label>
          <input class="form-control @error('joining_date') is-invalid @enderror" id="joining_date" name="joining_date"
                 type="date" value="{{ old('joining_date', $staff->joining_date) }}" required />
          @error('joining_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Status --}}
        <div class="col-md-6">
          <label class="form-label" for="status">Employment Status *</label>
          <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
            @foreach(\App\Models\Employee::STATUSES as $key => $label)
              <option value="{{ $key }}" {{ old('status', $staff->status) === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
          @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
      </div>

      {{-- ── Section 3: Salary Info ── --}}
      <div class="row navbar-vertical-label-wrapper mb-3 mt-4">
        <div class="col-auto navbar-vertical-label text-primary fw-bold">
          <span class="fas fa-money-bill-wave me-1"></span> Salary Information
        </div>
        <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="salary">Basic Salary (PKR) *</label>
          <div class="input-group">
            <span class="input-group-text">PKR</span>
            <input class="form-control @error('salary') is-invalid @enderror" id="salary" name="salary"
                   type="number" step="0.01" min="0" value="{{ old('salary', $staff->salary) }}" required />
          </div>
          @error('salary') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
      </div>

      {{-- ── Section 4: CMS Login Access ── --}}
      <div class="row navbar-vertical-label-wrapper mb-3 mt-4">
        <div class="col-auto navbar-vertical-label text-warning fw-bold">
          <span class="fas fa-key me-1"></span> CMS Login Access
        </div>
        <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
      </div>

      @php $hasLogin = $staff->user_id && $staff->user; @endphp

      <div class="row g-3">
        <div class="col-12">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="enable_login" name="enable_login" value="1"
                   {{ old('enable_login', $hasLogin ? '1' : '') ? 'checked' : '' }}
                   onchange="toggleLoginFields(this.checked)" />
            <label class="form-check-label fw-semi-bold" for="enable_login">
              Enable CMS Login Account for this employee
            </label>
            @if($hasLogin)
              <small class="d-block text-success">
                <span class="fas fa-check-circle me-1"></span>
                Currently active — login: <strong>{{ $staff->user->email }}</strong>
              </small>
            @else
              <small class="d-block text-muted">Only enable for staff who need system access.</small>
            @endif
          </div>
        </div>

        <div id="login-fields" class="{{ old('enable_login', $hasLogin) ? '' : 'd-none' }}">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label" for="login_email">Login Email *</label>
              <input class="form-control @error('login_email') is-invalid @enderror" id="login_email" name="login_email"
                     type="email" value="{{ old('login_email', $staff->user->email ?? '') }}" placeholder="staff@marquee.com" />
              @error('login_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
              <label class="form-label" for="login_password">
                Password
                @if($hasLogin) <small class="text-muted">(leave blank to keep current)</small> @else * @endif
              </label>
              <div class="input-group">
                <input class="form-control @error('login_password') is-invalid @enderror" id="login_password"
                       name="login_password" type="password" placeholder="{{ $hasLogin ? 'Leave blank to keep current' : 'Min 6 characters' }}" />
                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('login_password', this)">
                  <span class="fas fa-eye"></span>
                </button>
              </div>
              @error('login_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
              <label class="form-label" for="login_role_id">CMS Role *</label>
              <select class="form-select @error('login_role_id') is-invalid @enderror" id="login_role_id" name="login_role_id">
                <option value="">Select Role...</option>
                @foreach($roles as $role)
                  <option value="{{ $role->id }}"
                    {{ old('login_role_id', $staff->user->role_id ?? '') == $role->id ? 'selected' : '' }}>
                    {{ ucwords(str_replace('_', ' ', $role->name)) }}
                  </option>
                @endforeach
              </select>
              @error('login_role_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>
        </div>
      </div>

      {{-- Submit --}}
      <div class="mt-4 d-flex justify-content-end gap-2">
        <a class="btn btn-falcon-default btn-sm" href="{{ route('staff.index') }}">Cancel</a>
        <button class="btn btn-primary btn-sm" type="submit">
          <span class="fas fa-save me-1"></span> Update Employee
        </button>
      </div>
    </form>
  </div>
</div>
@endsection

@section('styles')
<link href="{{ asset('vendors/choices/choices.min.css') }}" rel="stylesheet">
@endsection

@section('scripts')
<script src="{{ asset('vendors/choices/choices.min.js') }}"></script>
<script>
  function toggleLoginFields(show) {
    const el = document.getElementById('login-fields');
    el.classList.toggle('d-none', !show);
  }

  function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('.fas');
    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
      input.type = 'password';
      icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
  }

  function setupPhoneMask(inputId) {
    const input = document.getElementById(inputId);
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
      if (input.value === placeholder) input.setSelectionRange(prefix.length, prefix.length);
    });

    input.addEventListener('click', function() {
      let val = input.value;
      let clean = val.startsWith(prefix) ? val.substring(prefix.length) : val;
      let digits = clean.replace(/\D/g, '').substring(0, maxDigits);
      input.setSelectionRange(prefix.length + digits.length, prefix.length + digits.length);
    });

    if (input.form) {
      input.form.addEventListener('submit', function() {
        input.value = input.value.replace(/-+$/, '');
      });
    }
  }

  document.addEventListener('DOMContentLoaded', function() {
    setupPhoneMask('mobile_number');

    ['designation', 'employment_type', 'branch_id', 'status', 'login_role_id'].forEach(function(id) {
      const el = document.getElementById(id);
      if (el) new Choices(el, { searchEnabled: true, shouldSort: false, itemSelectText: '' });
    });
  });
</script>
@endsection
