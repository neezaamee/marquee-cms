@extends('layouts.admin')

@section('title', 'Marquee Details')

@section('content')
<div class="row g-3 mb-3">
  <!-- Profile details card -->
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Marquee Tenant Profile</h5>
        <a class="btn btn-falcon-default btn-sm" href="{{ route('marquees.edit', $marquee->id) }}">
          <span class="fas fa-edit me-1" data-fa-transform="shrink-3"></span> Edit Profile
        </a>
      </div>
      <div class="card-body">
        <div class="d-flex align-items-center mb-4">
          @if($marquee->logo)
            <img src="{{ asset('storage/' . $marquee->logo) }}" alt="Logo" class="rounded me-3 border p-1" style="max-height: 80px;" />
          @else
            <div class="avatar avatar-3xl me-3">
              <div class="avatar-name rounded-circle bg-subtle-primary text-primary"><span>{{ substr($marquee->name, 0, 2) }}</span></div>
            </div>
          @endif
          <div>
            <h4>{{ $marquee->name }}</h4>
            <p class="mb-0 text-600">{{ $marquee->address }}, {{ $marquee->city }}, {{ $marquee->province }}</p>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-sm-6">
            <h6 class="text-500 mb-1">Email Address</h6>
            <p class="fw-semi-bold">{{ $marquee->email }}</p>
          </div>
          <div class="col-sm-6">
            <h6 class="text-500 mb-1">Phone Number</h6>
            <p class="fw-semi-bold">{{ $marquee->phone }}</p>
          </div>
          <div class="col-sm-4">
            <h6 class="text-500 mb-1">NTN</h6>
            <p class="fw-semi-bold">{{ $marquee->ntn ?? 'N/A' }}</p>
          </div>
          <div class="col-sm-4">
            <h6 class="text-500 mb-1">STRN</h6>
            <p class="fw-semi-bold">{{ $marquee->strn ?? 'N/A' }}</p>
          </div>
          <div class="col-sm-4">
            <h6 class="text-500 mb-1">Tax Authority</h6>
            <p class="fw-semi-bold"><span class="badge bg-secondary">{{ $marquee->tax_authority }}</span></p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Subscription card -->
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header bg-light">
        <h5 class="mb-0">Subscription Info</h5>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <h6 class="text-500 mb-1">Active Plan</h6>
          <h4 class="text-primary">{{ $marquee->owners->first() && $marquee->owners->first()->subscriptionPlan ? $marquee->owners->first()->subscriptionPlan->name : 'None' }}</h4>
        </div>
        <div class="mb-3">
          <h6 class="text-500 mb-1">Plan Price</h6>
          <p class="fw-semi-bold">Rs. {{ number_format($marquee->owners->first() && $marquee->owners->first()->subscriptionPlan ? $marquee->owners->first()->subscriptionPlan->price : 0, 2) }} / month</p>
        </div>
        <div class="mb-3">
          <h6 class="text-500 mb-1">Expires On</h6>
          <p class="fw-semi-bold text-danger">
            {{ $marquee->owners->first() && $marquee->owners->first()->subscription_ends_at ? $marquee->owners->first()->subscription_ends_at->format('M d, Y') : 'Never (Lifetime)' }}
          </p>
        </div>
        <hr />
        <div>
          <h6 class="text-500 mb-2">Usage Limits</h6>
          <ul class="list-unstyled mb-0">
            <li class="mb-1 d-flex justify-content-between fs-10">
              <span>Branches:</span>
              <strong class="text-700">{{ $marquee->branches->count() }} / {{ $marquee->subscriptionPlan->max_branches ?? '∞' }}</strong>
            </li>
            <li class="mb-1 d-flex justify-content-between fs-10">
              <span>Users / Staff:</span>
              <strong class="text-700">{{ $marquee->users->count() }} / {{ $marquee->subscriptionPlan->max_users ?? '∞' }}</strong>
            </li>
            <li class="d-flex justify-content-between fs-10">
              <span>Storage Limit:</span>
              <strong class="text-700">{{ $marquee->subscriptionPlan->storage_limit_mb ?? 0 }} MB</strong>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Branches List -->
<div class="card mb-3">
  <div class="card-header bg-light">
    <h5 class="mb-0">Associated Branches</h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive scrollbar">
      <table class="table table-sm table-striped fs-10 mb-0">
        <thead class="bg-200 text-900">
          <tr>
            <th class="px-3">Branch Name</th>
            <th>City</th>
            <th>Phone</th>
            <th>POS Status</th>
            <th class="text-center">Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse($marquee->branches as $branch)
            <tr>
              <td class="px-3 fw-semi-bold">{{ $branch->name }}</td>
              <td>{{ $branch->city }}</td>
              <td>{{ $branch->phone }}</td>
              <td>
                @if($branch->fbr_pos_id)
                  <span class="text-success"><span class="fas fa-check-circle me-1"></span>{{ $branch->fbr_pos_id }}</span>
                @else
                  <span class="text-muted"><span class="fas fa-times-circle me-1"></span>Not Integrated</span>
                @endif
              </td>
              <td class="text-center">
                <span class="badge badge-subtle-{{ $branch->status === 'active' ? 'success' : 'secondary' }} rounded-pill">
                  {{ ucfirst($branch->status) }}
                </span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center py-3">No branches registered.</td>
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
    <h5 class="mb-0">Staff Members / Users</h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive scrollbar">
      <table class="table table-sm table-striped fs-10 mb-0">
        <thead class="bg-200 text-900">
          <tr>
            <th class="px-3">Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Assigned Branch</th>
            <th class="text-center">Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse($marquee->users as $user)
            <tr>
              <td class="px-3 fw-semi-bold">{{ $user->name }}</td>
              <td>{{ $user->email }}</td>
              <td>{{ $user->role->label ?? 'None' }}</td>
              <td>{{ $user->branch->name ?? 'All Branches' }}</td>
              <td class="text-center">
                <span class="badge badge-subtle-{{ $user->status === 'active' ? 'success' : 'secondary' }} rounded-pill">
                  {{ ucfirst($user->status) }}
                </span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center py-3">No users registered.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
