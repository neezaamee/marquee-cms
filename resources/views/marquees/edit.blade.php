@extends('layouts.admin')

@section('title', 'Edit Marquee')

@section('content')
<div class="card mb-3">
  <div class="card-header bg-light d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Edit Marquee Tenant: {{ $marquee->name }}</h5>
    <a class="btn btn-falcon-default btn-sm" href="{{ route('marquees.index') }}">
      <span class="fas fa-chevron-left me-1" data-fa-transform="shrink-4"></span> Back
    </a>
  </div>
  
  <div class="card-body">
    <form action="{{ route('marquees.update', $marquee->id) }}" method="POST" enctype="multipart/form-data">
      @csrf
      @method('PUT')

      <div class="row g-3">
        <!-- Name -->
        <div class="col-md-6">
          <label class="form-label" for="name">Company / Marquee Name *</label>
          <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" type="text" value="{{ old('name', $marquee->name) }}" required />
          @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <!-- Email -->
        <div class="col-md-6">
          <label class="form-label" for="email">Email Address *</label>
          <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email', $marquee->email) }}" required />
          @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <!-- Phone -->
        <div class="col-md-6">
          <label class="form-label" for="phone">Phone Number *</label>
          <input class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" type="text" value="{{ old('phone', $marquee->phone) }}" required />
          @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <!-- Logo -->
        <div class="col-md-6">
          <label class="form-label" for="logo">Logo Image</label>
          <input class="form-control @error('logo') is-invalid @enderror" id="logo" name="logo" type="file" accept="image/*" />
          @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
          @if($marquee->logo)
            <div class="mt-2">
              <img src="{{ asset('storage/' . $marquee->logo) }}" alt="Logo" height="50" class="border rounded p-1" />
            </div>
          @endif
        </div>

        <!-- Address -->
        <div class="col-12">
          <label class="form-label" for="address">Address *</label>
          <input class="form-control @error('address') is-invalid @enderror" id="address" name="address" type="text" value="{{ old('address', $marquee->address) }}" required />
          @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <!-- Province -->
        <div class="col-md-4">
          <label class="form-label" for="province">Province *</label>
          <select class="form-select @error('province') is-invalid @enderror" id="province" name="province" required>
            <option value="">Select Province...</option>
            <option value="Punjab" {{ old('province', $marquee->province) === 'Punjab' ? 'selected' : '' }}>Punjab</option>
            <option value="Sindh" {{ old('province', $marquee->province) === 'Sindh' ? 'selected' : '' }}>Sindh</option>
            <option value="Khyber Pakhtunkhwa" {{ old('province', $marquee->province) === 'Khyber Pakhtunkhwa' ? 'selected' : '' }}>Khyber Pakhtunkhwa</option>
            <option value="Balochistan" {{ old('province', $marquee->province) === 'Balochistan' ? 'selected' : '' }}>Balochistan</option>
            <option value="Islamabad Capital Territory" {{ old('province', $marquee->province) === 'Islamabad Capital Territory' ? 'selected' : '' }}>Islamabad Capital Territory</option>
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
            <option value="active" {{ old('status', $marquee->status) === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status', $marquee->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
            <option value="suspended" {{ old('status', $marquee->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
          </select>
          @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="row navbar-vertical-label-wrapper mt-4 mb-2">
          <div class="col-auto navbar-vertical-label text-primary">Taxation & Subscription Information</div>
          <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
        </div>

        <!-- NTN -->
        <div class="col-md-4">
          <label class="form-label" for="ntn">NTN Number</label>
          <input class="form-control @error('ntn') is-invalid @enderror" id="ntn" name="ntn" type="text" value="{{ old('ntn', $marquee->ntn) }}" />
          @error('ntn') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <!-- STRN -->
        <div class="col-md-4">
          <label class="form-label" for="strn">STRN (Sales Tax Reg.)</label>
          <input class="form-control @error('strn') is-invalid @enderror" id="strn" name="strn" type="text" value="{{ old('strn', $marquee->strn) }}" />
          @error('strn') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <!-- Tax Authority -->
        <div class="col-md-4">
          <label class="form-label" for="tax_authority">Tax Authority *</label>
          <select class="form-select @error('tax_authority') is-invalid @enderror" id="tax_authority" name="tax_authority" required>
            <option value="FBR" {{ old('tax_authority', $marquee->tax_authority) === 'FBR' ? 'selected' : '' }}>FBR (Federal)</option>
            <option value="PRA" {{ old('tax_authority', $marquee->tax_authority) === 'PRA' ? 'selected' : '' }}>PRA (Punjab)</option>
            <option value="SRB" {{ old('tax_authority', $marquee->tax_authority) === 'SRB' ? 'selected' : '' }}>SRB (Sindh)</option>
            <option value="KPRA" {{ old('tax_authority', $marquee->tax_authority) === 'KPRA' ? 'selected' : '' }}>KPRA (KPK)</option>
            <option value="BRA" {{ old('tax_authority', $marquee->tax_authority) === 'BRA' ? 'selected' : '' }}>BRA (Balochistan)</option>
          </select>
          @error('tax_authority') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <!-- Subscription Plan -->
        <div class="col-md-6">
          <label class="form-label" for="subscription_plan_id">Subscription Plan *</label>
          <select class="form-select @error('subscription_plan_id') is-invalid @enderror" id="subscription_plan_id" name="subscription_plan_id" required>
            @foreach($plans as $plan)
              <option value="{{ $plan->id }}" {{ old('subscription_plan_id', $marquee->subscription_plan_id) == $plan->id ? 'selected' : '' }}>
                {{ $plan->name }} (Rs. {{ number_format($plan->price) }})
              </option>
            @endforeach
          </select>
          @error('subscription_plan_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <!-- Subscription Ends At -->
        <div class="col-md-6">
          <label class="form-label" for="subscription_ends_at">Subscription Ends At</label>
          <input class="form-control @error('subscription_ends_at') is-invalid @enderror" id="subscription_ends_at" name="subscription_ends_at" type="date" value="{{ old('subscription_ends_at', $marquee->subscription_ends_at ? $marquee->subscription_ends_at->format('Y-m-d') : '') }}" />
          @error('subscription_ends_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
      </div>

      <div class="mt-4 d-flex justify-content-end gap-2">
        <a class="btn btn-falcon-default btn-sm" href="{{ route('marquees.index') }}">Cancel</a>
        <button class="btn btn-primary btn-sm" type="submit">Update Marquee</button>
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
      const oldProvince = "{{ old('province', $marquee->province) }}";
      const oldCity = "{{ old('city', $marquee->city) }}";
      if (oldProvince) {
          updateCityChoices(oldProvince, oldCity);
      }
  });
</script>
@endsection
@endsection
