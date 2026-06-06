@extends('layouts.admin')

@section('title', 'Add New Branch')

@section('content')
<div class="card mb-3">
  <div class="card-header bg-light d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Add New Branch</h5>
    <a class="btn btn-falcon-default btn-sm" href="{{ route('branches.index') }}">
      <span class="fas fa-chevron-left me-1" data-fa-transform="shrink-4"></span> Back
    </a>
  </div>
  
  <div class="card-body">
    <form action="{{ route('branches.store') }}" method="POST">
      @csrf

      <div class="row g-3">
        <!-- Marquee selector for Super Admins -->
        @if(auth()->user()->isSuperAdmin())
          <div class="col-12">
            <label class="form-label" for="marquee_id">Select Marquee Tenant *</label>
            <select class="form-select @error('marquee_id') is-invalid @enderror" id="marquee_id" name="marquee_id" required>
              <option value="" disabled selected>Select a marquee tenant...</option>
              @foreach($marquees as $marquee)
                <option value="{{ $marquee->id }}" {{ old('marquee_id') == $marquee->id ? 'selected' : '' }}>
                  {{ $marquee->name }} ({{ $marquee->city }})
                </option>
              @endforeach
            </select>
            @error('marquee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        @endif

        <!-- Name -->
        <div class="col-md-6">
          <label class="form-label" for="name">Branch Name *</label>
          <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" type="text" value="{{ old('name') }}" required placeholder="e.g. Lahore Gulberg Branch" />
          @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <!-- Phone -->
        <div class="col-md-6">
          <label class="form-label" for="phone">Phone Number *</label>
          <input class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" type="text" value="{{ old('phone') }}" required />
          @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <!-- Address -->
        <div class="col-12">
          <label class="form-label" for="address">Address *</label>
          <input class="form-control @error('address') is-invalid @enderror" id="address" name="address" type="text" value="{{ old('address') }}" required />
          @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <!-- Province -->
        <div class="col-md-4">
          <label class="form-label" for="province">Province *</label>
          <select class="form-select @error('province') is-invalid @enderror" id="province" name="province" required>
            <option value="">Select Province...</option>
            <option value="Punjab" {{ old('province') === 'Punjab' ? 'selected' : '' }}>Punjab</option>
            <option value="Sindh" {{ old('province') === 'Sindh' ? 'selected' : '' }}>Sindh</option>
            <option value="Khyber Pakhtunkhwa" {{ old('province') === 'Khyber Pakhtunkhwa' ? 'selected' : '' }}>Khyber Pakhtunkhwa</option>
            <option value="Balochistan" {{ old('province') === 'Balochistan' ? 'selected' : '' }}>Balochistan</option>
            <option value="Islamabad Capital Territory" {{ old('province') === 'Islamabad Capital Territory' ? 'selected' : '' }}>Islamabad Capital Territory</option>
          </select>
          @error('province') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <!-- City -->
        <div class="col-md-4">
          <label class="form-label" for="city">City *</label>
          <select class="form-select @error('city') is-invalid @enderror" id="city" name="city" required>
            <option value="">Select City...</option>
          </select>
          @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <!-- Status -->
        <div class="col-md-4">
          <label class="form-label" for="status">Status *</label>
          <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
          </select>
          @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="row navbar-vertical-label-wrapper mt-4 mb-2">
          <div class="col-auto navbar-vertical-label text-primary">FBR POS Integration Settings (Optional)</div>
          <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
        </div>

        <!-- FBR POS ID -->
        <div class="col-md-5">
          <label class="form-label" for="fbr_pos_id">FBR POS Device ID</label>
          <input class="form-control @error('fbr_pos_id') is-invalid @enderror" id="fbr_pos_id" name="fbr_pos_id" type="text" value="{{ old('fbr_pos_id') }}" placeholder="e.g. PRA-LHR-GUL-01" />
          @error('fbr_pos_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <!-- FBR POS Key -->
        <div class="col-md-5">
          <label class="form-label" for="fbr_pos_key">POS Authorization Key</label>
          <input class="form-control @error('fbr_pos_key') is-invalid @enderror" id="fbr_pos_key" name="fbr_pos_key" type="password" placeholder="Key / Token secret" />
          @error('fbr_pos_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <!-- FBR Sandbox Mode -->
        <div class="col-md-2 d-flex align-items-center mt-md-4">
          <div class="form-check mb-0">
            <input class="form-check-input" type="checkbox" id="fbr_sandbox_mode" name="fbr_sandbox_mode" value="1" {{ old('fbr_sandbox_mode', true) ? 'checked' : '' }} />
            <label class="form-check-label mb-0" for="fbr_sandbox_mode">Sandbox Mode</label>
          </div>
        </div>
      </div>

      <div class="mt-4 d-flex justify-content-end gap-2">
        <a class="btn btn-falcon-default btn-sm" href="{{ route('branches.index') }}">Cancel</a>
        <button class="btn btn-primary btn-sm" type="submit">Save Branch</button>
      </div>
    </form>
  </div>
</div>

@section('styles')
<link href="{{ asset('vendors/choices/choices.min.css') }}" rel="stylesheet">
@endsection

@section('scripts')
<script src="{{ asset('vendors/choices/choices.min.js') }}"></script>
<script>
  const citiesByProvince = {
    "Punjab": ["Lahore", "Faisalabad", "Rawalpindi", "Gujranwala", "Multan", "Bahawalpur", "Sargodha", "Sialkot", "Sheikhupura", "Rahim Yar Khan"],
    "Sindh": ["Karachi", "Hyderabad", "Sukkur", "Larkana", "Nawabshah", "Mirpur Khas"],
    "Khyber Pakhtunkhwa": ["Peshawar", "Mardan", "Mingora", "Kohat", "Abbottabad", "Dera Ismail Khan"],
    "Balochistan": ["Quetta", "Gwadar", "Khuzdar", "Turbat", "Sibi"],
    "Islamabad Capital Territory": ["Islamabad"]
  };

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
      // 1. Setup Phone Mask
      setupPhoneMask('#phone');

      // 2. Setup Choices.js for Province and City
      const provinceEl = document.getElementById('province');
      const cityEl = document.getElementById('city');

      const provinceChoices = new Choices(provinceEl, {
          searchEnabled: true,
          shouldSort: false,
          itemSelectText: ''
      });

      const cityChoices = new Choices(cityEl, {
          searchEnabled: true,
          shouldSort: false,
          itemSelectText: ''
      });

      function updateCityChoices(province, selectedCity = null) {
          cityChoices.clearStore();
          cityChoices.setValue([]);

          let choicesList = [{ value: '', label: 'Select City...', selected: true, disabled: false }];
          
          if (province && citiesByProvince[province]) {
              citiesByProvince[province].forEach(city => {
                  choicesList.push({
                      value: city,
                      label: city,
                      selected: city === selectedCity
                  });
              });
          }

          cityChoices.setChoices(choicesList, 'value', 'label', true);
      }

      provinceEl.addEventListener('change', function(e) {
          updateCityChoices(e.target.value);
      });

      // Handle old inputs/pre-selection
      const oldProvince = "{{ old('province') }}";
      const oldCity = "{{ old('city') }}";
      if (oldProvince) {
          updateCityChoices(oldProvince, oldCity);
      }
  });
</script>
@endsection
@endsection
