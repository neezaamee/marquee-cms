@extends('layouts.admin')

@section('title', 'Employee Profile: ' . $staff->name)

@section('content')
<div class="row g-3">
  {{-- Left Column: Profile Card --}}
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-body text-center pt-4">
        {{-- Photo --}}
        @if($staff->photo)
          <img src="{{ asset('storage/' . $staff->photo) }}" alt="{{ $staff->name }}"
               class="rounded-circle mb-3 border border-3"
               style="width:120px;height:120px;object-fit:cover;border-color:var(--falcon-primary)!important">
        @else
          <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center bg-200 border border-3"
               style="width:120px;height:120px;border-color:var(--falcon-primary)!important">
            <span class="fas fa-user fa-3x text-500"></span>
          </div>
        @endif

        <h5 class="mb-1">{{ $staff->name }}</h5>
        <p class="text-muted mb-1">{{ $staff->designation }}</p>
        <span class="badge badge-subtle-secondary font-monospace fs-11 mb-3">{{ $staff->employee_id }}</span>

        {{-- Status Badge --}}
        @php
          $sc = ['active' => 'success', 'inactive' => 'secondary', 'resigned' => 'warning', 'terminated' => 'danger'];
        @endphp
        <div class="mb-3">
          <span class="badge badge-subtle-{{ $sc[$staff->status] ?? 'secondary' }} rounded-pill fs-10">
            {{ ucfirst($staff->status) }}
          </span>
        </div>

        {{-- CMS Login Badge --}}
        @if($staff->users->isNotEmpty())
          <div class="alert alert-success py-2 px-3 fs-10 mb-3 text-start" role="alert">
            <div class="fw-bold mb-1"><span class="fas fa-key me-1"></span>CMS Login Profiles:</div>
            @foreach($staff->users as $u)
              <div class="border-bottom border-200 py-1" style="border-bottom-style: dashed !important;">
                <strong>Email:</strong> {{ $u->email }}<br>
                <strong>Username:</strong> {{ $u->username }}<br>
                <strong>Branch:</strong> {{ $u->branch->name ?? 'All Branches' }}<br>
                <strong>Role:</strong> {{ ucwords(str_replace('_', ' ', $u->role->name ?? '—')) }}
              </div>
            @endforeach
          </div>
        @else
          <div class="alert alert-light border py-2 px-3 fs-10 mb-3" role="alert">
            <span class="fas fa-lock me-1 text-muted"></span>
            No CMS login access
          </div>
        @endif

        {{-- Action Buttons --}}
        <div class="d-flex gap-2 justify-content-center">
          <a href="{{ route('staff.edit', $staff->id) }}" class="btn btn-falcon-primary btn-sm">
            <span class="fas fa-edit me-1"></span> Edit
          </a>
          <a href="{{ route('staff.index') }}" class="btn btn-falcon-default btn-sm">
            <span class="fas fa-list me-1"></span> All Staff
          </a>
        </div>
      </div>
    </div>
  </div>

  {{-- Right Column: Details --}}
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header bg-light">
        <h6 class="mb-0"><span class="fas fa-info-circle me-2"></span>Employee Details</h6>
      </div>
      <div class="card-body">

        {{-- Row 1: Contact & ID --}}
        <div class="row navbar-vertical-label-wrapper mb-2 mt-1">
          <div class="col-auto navbar-vertical-label text-primary">Contact Information</div>
          <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
        </div>
        <dl class="row fs-10">
          <dt class="col-sm-4 text-muted">Mobile Number</dt>
          <dd class="col-sm-8">{{ $staff->mobile_number }}</dd>

          <dt class="col-sm-4 text-muted">CNIC</dt>
          <dd class="col-sm-8 font-monospace">{{ $staff->cnic }}</dd>
        </dl>

        {{-- Row 2: Job Info --}}
        <div class="row navbar-vertical-label-wrapper mb-2 mt-3">
          <div class="col-auto navbar-vertical-label text-primary">Job Information</div>
          <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
        </div>
        <dl class="row fs-10">
          <dt class="col-sm-4 text-muted">Branch</dt>
          <dd class="col-sm-8">{{ $staff->branch->name ?? '—' }}</dd>

          <dt class="col-sm-4 text-muted">Designation</dt>
          <dd class="col-sm-8">{{ $staff->designation }}</dd>

          <dt class="col-sm-4 text-muted">Employment Type</dt>
          <dd class="col-sm-8">
            @php
              $typeColors = ['Permanent' => 'success', 'Contract' => 'info', 'Daily Wages' => 'warning', 'Part-Time' => 'secondary'];
              $tc = $typeColors[$staff->employment_type] ?? 'secondary';
            @endphp
            <span class="badge badge-subtle-{{ $tc }}">{{ $staff->employment_type }}</span>
          </dd>

          <dt class="col-sm-4 text-muted">Joining Date</dt>
          <dd class="col-sm-8">{{ \Carbon\Carbon::parse($staff->joining_date)->format('d M, Y') }}</dd>

          <dt class="col-sm-4 text-muted">Status</dt>
          <dd class="col-sm-8">
            <span class="badge badge-subtle-{{ $sc[$staff->status] ?? 'secondary' }} rounded-pill">
              {{ ucfirst($staff->status) }}
            </span>
          </dd>
        </dl>

        {{-- Row 3: Salary Info --}}
        <div class="row navbar-vertical-label-wrapper mb-2 mt-3">
          <div class="col-auto navbar-vertical-label text-primary">Salary Information</div>
          <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
        </div>
        <dl class="row fs-10">
          <dt class="col-sm-4 text-muted">Basic Salary</dt>
          <dd class="col-sm-8 fw-bold text-success fs-9">PKR {{ number_format($staff->salary, 0) }}</dd>
        </dl>

        {{-- Row 4: System Info --}}
        <div class="row navbar-vertical-label-wrapper mb-2 mt-3">
          <div class="col-auto navbar-vertical-label text-muted">System Information</div>
          <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
        </div>
        <dl class="row fs-10">
          <dt class="col-sm-4 text-muted">Employee ID</dt>
          <dd class="col-sm-8 font-monospace">{{ $staff->employee_id }}</dd>

          <dt class="col-sm-4 text-muted">Record Created</dt>
          <dd class="col-sm-8">{{ $staff->created_at->format('d M, Y H:i') }}</dd>

          <dt class="col-sm-4 text-muted">Last Updated</dt>
          <dd class="col-sm-8">{{ $staff->updated_at->format('d M, Y H:i') }}</dd>
        </dl>
      </div>
    </div>

    {{-- Placeholder: Future Modules --}}
    <div class="card mt-3">
      <div class="card-header bg-light">
        <h6 class="mb-0 text-muted">
          <span class="fas fa-clock me-2"></span>Coming Soon: Attendance & Payroll History
        </h6>
      </div>
      <div class="card-body text-center py-4">
        <span class="fas fa-chart-bar fa-2x text-200 mb-2 d-block"></span>
        <p class="text-muted mb-0 fs-10">
          Attendance records, payroll history, and increment logs will appear here once those modules are implemented.
        </p>
      </div>
    </div>
  </div>
</div>
@endsection
