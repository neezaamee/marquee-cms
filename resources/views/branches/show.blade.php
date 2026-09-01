@extends('layouts.admin')

@section('title', 'Branch Details')

@section('content')
<div class="row g-3 mb-3">
  <!-- Profile details card -->
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Branch Location Profile</h5>
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'))
          <a class="btn btn-falcon-default btn-sm" href="{{ route('branches.edit', $branch->id) }}">
            <span class="fas fa-edit me-1" data-fa-transform="shrink-3"></span> Edit Branch
          </a>
        @endif
      </div>
      <div class="card-body">
        <h4 class="text-primary mb-2">{{ $branch->name }}</h4>
        @if(auth()->user()->isSuperAdmin())
          <h6 class="text-600 mb-3">Tenant: <a href="{{ route('marquees.show', $branch->marquee->id) }}">{{ $branch->marquee->name }}</a></h6>
        @endif

        <div class="row g-3 mt-1">
          <div class="col-sm-12">
            <h6 class="text-500 mb-1">Address</h6>
            <p class="fw-semi-bold">{{ $branch->address }}, {{ $branch->city }}, {{ $branch->province }}</p>
          </div>
          <div class="col-sm-6">
            <h6 class="text-500 mb-1">Phone Number</h6>
            <p class="fw-semi-bold">{{ $branch->phone }}</p>
          </div>
          <div class="col-sm-6">
            <h6 class="text-500 mb-1">Status</h6>
            <p class="fw-semi-bold">
              <span class="badge badge-subtle-{{ $branch->status === 'active' ? 'success' : 'secondary' }} rounded-pill">
                {{ ucfirst($branch->status) }}
              </span>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- FBR details card -->
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header bg-light">
        <h5 class="mb-0">FBR POS Integration</h5>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <h6 class="text-500 mb-1">Device / POS ID</h6>
          <p class="fw-semi-bold fs-9 text-700">{{ $branch->fbr_pos_id ?? 'Not Integrated' }}</p>
        </div>
        <div class="mb-3">
          <h6 class="text-500 mb-1">Integration Status</h6>
          <p class="fw-semi-bold">
            @if($branch->fbr_pos_id)
              <span class="badge badge-subtle-success rounded-pill">Integrated</span>
            @else
              <span class="badge badge-subtle-secondary rounded-pill">Pending Setup</span>
            @endif
          </p>
        </div>
        <div class="mb-3">
          <h6 class="text-500 mb-1">POS Mode</h6>
          <p class="fw-semi-bold">
            @if($branch->fbr_sandbox_mode)
              <span class="badge badge-subtle-warning rounded-pill">Sandbox / Test</span>
            @else
              <span class="badge badge-subtle-danger rounded-pill">Live / Production</span>
            @endif
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Halls and Venues List -->
<div class="card mb-3">
  <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="mb-0">Halls & Venues ({{ $branch->halls->count() }})</h5>
    @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('create_halls'))
      <a class="btn btn-falcon-primary btn-sm" href="{{ route('halls.create', ['branch_id' => $branch->id]) }}">
        <span class="fas fa-plus me-1" data-fa-transform="shrink-3"></span> Add Hall to this Branch
      </a>
    @endif
  </div>
  <div class="card-body p-0">
    <div class="table-responsive scrollbar">
      <table class="table table-sm table-striped fs-10 mb-0">
        <thead class="bg-200 text-900">
          <tr>
            <th class="px-3">Hall Name</th>
            <th>Hall Code</th>
            <th>Hall Type</th>
            <th>Capacity</th>
            <th>Default Rent (PKR)</th>
            <th class="text-center">Status</th>
            <th class="text-end px-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($branch->halls as $hall)
            <tr>
              <td class="px-3 fw-semi-bold">
                <a href="{{ route('halls.show', $hall->id) }}">{{ $hall->hall_name }}</a>
              </td>
              <td><code>{{ $hall->hall_code }}</code></td>
              <td><span class="badge badge-subtle-info">{{ $hall->hall_type }}</span></td>
              <td>{{ number_format($hall->capacity) }} Guests</td>
              <td class="font-monospace">Rs. {{ number_format($hall->default_booking_price, 2) }}</td>
              <td class="text-center">
                <span class="badge badge-subtle-{{ $hall->status === 'active' ? 'success' : 'secondary' }} rounded-pill">
                  {{ ucfirst($hall->status) }}
                </span>
              </td>
              <td class="text-end px-3">
                <div class="d-flex justify-content-end gap-2">
                  <a class="btn btn-link p-0" href="{{ route('halls.show', $hall->id) }}" title="View">
                    <span class="text-info fas fa-eye"></span>
                  </a>
                  @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('edit_halls'))
                    <a class="btn btn-link p-0" href="{{ route('halls.edit', $hall->id) }}" title="Edit">
                      <span class="text-primary fas fa-edit"></span>
                    </a>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-4 text-muted">
                No halls registered in this branch yet.
                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('create_halls'))
                  <div class="mt-2">
                    <a class="btn btn-sm btn-primary" href="{{ route('halls.create', ['branch_id' => $branch->id]) }}">
                      <span class="fas fa-plus me-1"></span> Add First Hall
                    </a>
                  </div>
                @endif
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Users List -->
<div class="card mb-3">
  <div class="card-header bg-light">
    <h5 class="mb-0">Staff Assigned to this Branch</h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive scrollbar">
      <table class="table table-sm table-striped fs-10 mb-0">
        <thead class="bg-200 text-900">
          <tr>
            <th class="px-3">Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Phone</th>
            <th class="text-center">Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse($branch->users as $user)
            <tr>
              <td class="px-3 fw-semi-bold">{{ $user->name }}</td>
              <td>{{ $user->email }}</td>
              <td>{{ $user->role->label ?? 'None' }}</td>
              <td>{{ $user->phone ?? 'N/A' }}</td>
              <td class="text-center">
                <span class="badge badge-subtle-{{ $user->status === 'active' ? 'success' : 'secondary' }} rounded-pill">
                  {{ ucfirst($user->status) }}
                </span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center py-3">No staff members assigned to this branch.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
