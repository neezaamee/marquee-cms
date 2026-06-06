@extends('layouts.admin')

@section('title', 'User Profile')

@section('content')
<div class="row g-3 mb-3">
  <!-- Profile info card -->
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">User Profile</h5>
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_staff'))
          <a class="btn btn-falcon-default btn-sm" href="{{ route('users.edit', $user->id) }}">
            <span class="fas fa-edit me-1" data-fa-transform="shrink-3"></span> Edit Profile
          </a>
        @endif
      </div>
      <div class="card-body">
        <div class="d-flex align-items-center mb-4">
          <div class="avatar avatar-3xl me-3">
            <div class="avatar-name rounded-circle bg-subtle-primary text-primary"><span>{{ substr($user->name, 0, 2) }}</span></div>
          </div>
          <div>
            <h4>{{ $user->name }}</h4>
            <p class="mb-0 text-600">{{ $user->role->label ?? 'No Assigned Role' }}</p>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-sm-6">
            <h6 class="text-500 mb-1">Email Address</h6>
            <p class="fw-semi-bold">{{ $user->email }}</p>
          </div>
          <div class="col-sm-6">
            <h6 class="text-500 mb-1">Phone Number</h6>
            <p class="fw-semi-bold">{{ $user->phone ?? 'N/A' }}</p>
          </div>
          <div class="col-sm-6">
            <h6 class="text-500 mb-1">Assigned Branch</h6>
            <p class="fw-semi-bold">{{ $user->branch->name ?? 'All Branches (Company-wide)' }}</p>
          </div>
          <div class="col-sm-6">
            <h6 class="text-500 mb-1">Marquee Company</h6>
            <p class="fw-semi-bold">{{ $user->marquee->name ?? 'SaaS Administration' }}</p>
          </div>
          <div class="col-sm-6">
            <h6 class="text-500 mb-1">Account Status</h6>
            <p class="fw-semi-bold">
              <span class="badge badge-subtle-{{ $user->status === 'active' ? 'success' : 'secondary' }} rounded-pill">
                {{ ucfirst($user->status) }}
              </span>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Role & Permissions card -->
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header bg-light">
        <h5 class="mb-0">Role & Access Level</h5>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <h6 class="text-500 mb-1">Role Title</h6>
          <h4 class="text-primary">{{ $user->role->label ?? 'None' }}</h4>
          <p class="text-600 fs-10 mt-1">{{ $user->role->description ?? 'No description available for this role.' }}</p>
        </div>
        <hr />
        <div>
          <h6 class="text-500 mb-2">Enabled Permissions</h6>
          @if($user->isSuperAdmin())
            <span class="badge badge-subtle-danger rounded-pill mb-1">Full Root System Access</span>
          @elseif($user->role && $user->role->permissions->isNotEmpty())
            <div class="d-flex flex-wrap gap-1">
              @foreach($user->role->permissions as $permission)
                <span class="badge badge-subtle-success rounded-pill">{{ $permission->label }}</span>
              @endforeach
            </div>
          @else
            <p class="text-muted fs-11">No permissions assigned to this role.</p>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
