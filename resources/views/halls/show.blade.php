@extends('layouts.admin')

@section('title', 'Hall Details')

@section('content')
<div class="row g-3">
  <!-- Hall Details Card -->
  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Hall Details: {{ ucwords($hall->hall_name) }}</h5>
        <div class="d-flex gap-2">
          <a class="btn btn-falcon-default btn-sm" href="{{ route('halls.index') }}">
            <span class="fas fa-chevron-left me-1" data-fa-transform="shrink-4"></span> Back
          </a>
          @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('edit_halls'))
            <a class="btn btn-falcon-primary btn-sm" href="{{ route('halls.edit', $hall->id) }}">
              <span class="fas fa-edit me-1"></span> Edit Hall
            </a>
          @endif
        </div>
      </div>
      
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <h6 class="text-muted fs-11 text-uppercase fw-bold">Hall Code</h6>
            <p class="fs-9 fw-semi-bold">{{ strtoupper($hall->hall_code) }}</p>
          </div>
          <div class="col-md-6 mb-3">
            <h6 class="text-muted fs-11 text-uppercase fw-bold">Branch Location</h6>
            <p class="fs-9">{{ $hall->branch->name ?? 'N/A' }} ({{ $hall->branch->city ?? '' }})</p>
          </div>
          <div class="col-md-6 mb-3">
            <h6 class="text-muted fs-11 text-uppercase fw-bold">Guest Capacity</h6>
            <p class="fs-9"><span class="fas fa-users me-1 text-primary"></span>{{ number_format($hall->capacity) }} Guests</p>
          </div>
          <div class="col-md-6 mb-3">
            <h6 class="text-muted fs-11 text-uppercase fw-bold">Venue Type</h6>
            <p class="fs-9"><span class="badge badge-subtle-primary">{{ ucfirst($hall->hall_type) }}</span></p>
          </div>
          <div class="col-md-6 mb-3">
            <h6 class="text-muted fs-11 text-uppercase fw-bold">Default Booking Price</h6>
            <p class="fs-9 text-success fw-bold">PKR {{ number_format($hall->default_booking_price, 2) }}</p>
          </div>
          <div class="col-md-6 mb-3">
            <h6 class="text-muted fs-11 text-uppercase fw-bold">Status</h6>
            <p class="fs-9">
              <span class="badge badge-subtle-{{ $hall->status === 'active' ? 'success' : 'secondary' }} rounded-pill">
                {{ ucfirst($hall->status) }}
              </span>
            </p>
          </div>
          <div class="col-12 mb-3">
            <h6 class="text-muted fs-11 text-uppercase fw-bold">Description</h6>
            <p class="fs-10 text-muted">{{ $hall->description ?? 'No description provided.' }}</p>
          </div>
          <div class="col-12 border-top pt-3">
            <p class="mb-0 text-muted fs-11">
              Registered by: <strong>{{ $hall->creator->name ?? 'System' }}</strong> | Created at: {{ $hall->created_at->format('M d, Y h:i A') }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Assigned Slots Card -->
  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Assigned Slots</h5>
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings') || auth()->user()->hasPermission('edit_halls'))
          <a class="btn btn-link btn-sm p-0" href="{{ route('hall-slots.index', ['selectedHallId' => $hall->id, 'branch_id' => $hall->branch_id]) }}">Manage</a>
        @endif
      </div>
      <div class="card-body">
        @if(count($hall->slots) > 0)
          <ul class="list-group list-group-flush">
            @foreach($hall->slots as $slot)
              <li class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="mb-1 fw-bold">{{ $slot->slot_name }}</h6>
                  <span class="text-muted fs-11">
                    {{ \Carbon\Carbon::parse($slot->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($slot->end_time)->format('h:i A') }}
                  </span>
                </div>
                <span class="badge badge-subtle-success rounded-pill">Active</span>
              </li>
            @endforeach
          </ul>
        @else
          <div class="text-center py-4 text-muted">
            <span class="fas fa-clock fs-3 mb-2 d-block"></span>
            <p class="mb-0 fs-10">No shift slots assigned to this hall yet.</p>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
