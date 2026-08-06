@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
@if(session('warning'))
<div class="alert alert-warning border-2 d-flex align-items-center mb-3 alert-dismissible fade show" role="alert">
  <span class="fas fa-exclamation-triangle me-2"></span>
  <p class="mb-0 flex-1">{{ session('warning') }}</p>
  <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('success'))
<div class="alert alert-success border-2 d-flex align-items-center mb-3 alert-dismissible fade show" role="alert">
  <span class="fas fa-check-circle me-2"></span>
  <p class="mb-0 flex-1">{{ session('success') }}</p>
  <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(!$isSetupCompleted)
<!-- Onboarding / Setup Progress Widget -->
<div class="card mb-3 border border-warning border-translucent" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.05) 0%, rgba(217, 119, 6, 0.05) 100%)">
  <div class="card-body p-4">
    <div class="row align-items-center justify-content-between g-3">
      <div class="col-lg-8 col-md-7">
        <h4 class="text-warning mb-1"><span class="fas fa-magic me-2"></span>Initial Business Configuration Required</h4>
        <p class="lead fs-10 text-700 mb-0">To ensure ledger integrity and enable event operations, please complete the essential tasks listed below. Rest of the CMS modules will remain locked until setup is complete.</p>
      </div>
      <div class="col-lg-4 col-md-5 text-md-end">
        <a class="btn btn-warning" href="{{ route('setup.wizard') }}">Open Setup Wizard <span class="fas fa-chevron-right ms-1"></span></a>
      </div>
    </div>
    
    <div class="row g-3 mt-3">
      <!-- Task 1: Marquee Info -->
      <div class="col">
        <div class="p-3 bg-body border border-translucent rounded-3 text-center position-relative h-100">
          <div class="avatar avatar-2xl mb-2 mx-auto">
            <span class="avatar-name rounded-circle {{ $setupChecklist['marquee_info'] ? 'bg-subtle-success text-success' : 'bg-subtle-secondary text-secondary' }}">
              <i class="fas {{ $setupChecklist['marquee_info'] ? 'fa-check' : 'fa-building' }}"></i>
            </span>
          </div>
          <h6 class="mb-1 text-900 fs-10">Business Profile</h6>
          <span class="badge badge-subtle-{{ $setupChecklist['marquee_info'] ? 'success' : 'secondary' }} rounded-pill" style="font-size: 8px;">
            {{ $setupChecklist['marquee_info'] ? 'Completed ✔' : 'Pending ✖' }}
          </span>
          <a class="stretched-link" href="{{ route('setup.wizard', ['step' => 1]) }}"></a>
        </div>
      </div>

      <!-- Task 2: Branch Info -->
      <div class="col">
        <div class="p-3 bg-body border border-translucent rounded-3 text-center position-relative h-100">
          <div class="avatar avatar-2xl mb-2 mx-auto">
            <span class="avatar-name rounded-circle {{ $setupChecklist['branch'] ? 'bg-subtle-success text-success' : 'bg-subtle-secondary text-secondary' }}">
              <i class="fas {{ $setupChecklist['branch'] ? 'fa-check' : 'fa-map-marker-alt' }}"></i>
            </span>
          </div>
          <h6 class="mb-1 text-900 fs-10">Branch Setup</h6>
          <span class="badge badge-subtle-{{ $setupChecklist['branch'] ? 'success' : 'secondary' }} rounded-pill" style="font-size: 8px;">
            {{ $setupChecklist['branch'] ? 'Completed ✔' : 'Pending ✖' }}
          </span>
          <a class="stretched-link" href="{{ route('setup.wizard', ['step' => 2]) }}"></a>
        </div>
      </div>

      <!-- Task 3: Hall Info -->
      <div class="col">
        <div class="p-3 bg-body border border-translucent rounded-3 text-center position-relative h-100">
          <div class="avatar avatar-2xl mb-2 mx-auto">
            <span class="avatar-name rounded-circle {{ $setupChecklist['hall'] ? 'bg-subtle-success text-success' : 'bg-subtle-secondary text-secondary' }}">
              <i class="fas {{ $setupChecklist['hall'] ? 'fa-check' : 'fa-hotel' }}"></i>
            </span>
          </div>
          <h6 class="mb-1 text-900 fs-10">Hall Venue</h6>
          <span class="badge badge-subtle-{{ $setupChecklist['hall'] ? 'success' : 'secondary' }} rounded-pill" style="font-size: 8px;">
            {{ $setupChecklist['hall'] ? 'Completed ✔' : 'Pending ✖' }}
          </span>
          <a class="stretched-link" href="{{ route('setup.wizard', ['step' => 3]) }}"></a>
        </div>
      </div>

      <!-- Task 4: Financial Year -->
      <div class="col">
        <div class="p-3 bg-body border border-translucent rounded-3 text-center position-relative h-100">
          <div class="avatar avatar-2xl mb-2 mx-auto">
            <span class="avatar-name rounded-circle {{ $setupChecklist['financial_year'] ? 'bg-subtle-success text-success' : 'bg-subtle-secondary text-secondary' }}">
              <i class="fas {{ $setupChecklist['financial_year'] ? 'fa-check' : 'fa-calendar-alt' }}"></i>
            </span>
          </div>
          <h6 class="mb-1 text-900 fs-10">Financial Year</h6>
          <span class="badge badge-subtle-{{ $setupChecklist['financial_year'] ? 'success' : 'secondary' }} rounded-pill" style="font-size: 8px;">
            {{ $setupChecklist['financial_year'] ? 'Completed ✔' : 'Pending ✖' }}
          </span>
          <a class="stretched-link" href="{{ route('setup.wizard', ['step' => 4]) }}"></a>
        </div>
      </div>

      <!-- Task 5: Event Types -->
      <div class="col">
        <div class="p-3 bg-body border border-translucent rounded-3 text-center position-relative h-100">
          <div class="avatar avatar-2xl mb-2 mx-auto">
            <span class="avatar-name rounded-circle {{ $setupChecklist['event_types'] ? 'bg-subtle-success text-success' : 'bg-subtle-secondary text-secondary' }}">
              <i class="fas {{ $setupChecklist['event_types'] ? 'fa-check' : 'fa-ticket-alt' }}"></i>
            </span>
          </div>
          <h6 class="mb-1 text-900 fs-10">Event Types</h6>
          <span class="badge badge-subtle-{{ $setupChecklist['event_types'] ? 'success' : 'secondary' }} rounded-pill" style="font-size: 8px;">
            {{ $setupChecklist['event_types'] ? 'Completed ✔' : 'Pending ✖' }}
          </span>
          <a class="stretched-link" href="{{ route('setup.wizard', ['step' => 5]) }}"></a>
        </div>
      </div>
    </div>
  </div>
</div>
@endif

<!-- Welcome Header Banner -->
<div class="card mb-3">
  <div class="card-body overflow-hidden p-lg-6">
    <div class="row align-items-center">
      <div class="col-lg-6">
        <img class="img-fluid" src="{{ asset('assets/img/icons/spot-illustrations/21.png') }}" alt="Welcome illustration" width="350" />
      </div>
      <div class="col-lg-6 ps-lg-4 my-5 text-center text-lg-start">
        <h3 class="text-primary">Welcome to Royal Marquee CMS!</h3>
        <p class="lead">Manage your banquet halls, customer bookings, custom menus, and payments seamlessly in one unified interface.</p>
        <a class="btn btn-falcon-primary" href="#!">Create New Booking</a>
      </div>
    </div>
  </div>
</div>

<!-- Metrics Cards Row -->
<div class="row g-3 mb-3">
  
  <!-- Total Bookings -->
  <div class="col-sm-6 col-md-3">
    <div class="card overflow-hidden" style="min-width: 12rem">
      <div class="bg-holder bg-card" style="background-image:url({{ asset('assets/img/icons/spot-illustrations/corner-1.png') }});"></div>
      <div class="card-body position-relative">
        <h6 class="text-uppercase text-600">Total Bookings</h6>
        <div class="display-4 fs-4 mb-2 fw-normal font-sans-serif text-warning">{{ $totalBookings }}</div>
        <a class="fw-semi-bold fs-10" href="{{ route('bookings.index') }}">View details <span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
      </div>
    </div>
  </div>

  <!-- Venues / Halls -->
  <div class="col-sm-6 col-md-3">
    <div class="card overflow-hidden" style="min-width: 12rem">
      <div class="bg-holder bg-card" style="background-image:url({{ asset('assets/img/icons/spot-illustrations/corner-2.png') }});"></div>
      <div class="card-body position-relative">
        <h6 class="text-uppercase text-600">Active Halls</h6>
        <div class="display-4 fs-4 mb-2 fw-normal font-sans-serif text-info">{{ $activeHalls }}</div>
        <a class="fw-semi-bold fs-10" href="{{ route('halls.index') }}">Manage venues <span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
      </div>
    </div>
  </div>

  <!-- Menu Packages -->
  <div class="col-sm-6 col-md-3">
    <div class="card overflow-hidden" style="min-width: 12rem">
      <div class="bg-holder bg-card" style="background-image:url({{ asset('assets/img/icons/spot-illustrations/corner-3.png') }});"></div>
      <div class="card-body position-relative">
        <h6 class="text-uppercase text-600">Menu Packages</h6>
        <div class="display-4 fs-4 mb-2 fw-normal font-sans-serif text-success">{{ $menuPackages }}</div>
        <a class="fw-semi-bold fs-10" href="{{ route('packages.index') }}">View menus <span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
      </div>
    </div>
  </div>

  <!-- Monthly Revenue -->
  <div class="col-sm-6 col-md-3">
    <div class="card overflow-hidden" style="min-width: 12rem">
      <div class="bg-holder bg-card" style="background-image:url({{ asset('assets/img/icons/spot-illustrations/corner-4.png') }});"></div>
      <div class="card-body position-relative">
        <h6 class="text-uppercase text-600">Monthly Revenue</h6>
        <div class="display-4 fs-4 mb-2 fw-normal font-sans-serif text-primary">Rs. {{ number_format($monthlyRevenue, 0) }}</div>
        <a class="fw-semi-bold fs-10" href="{{ route('finance.payments') }}">View transactions <span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
      </div>
    </div>
  </div>

</div>

<!-- Quick Actions & Recent Bookings placeholder -->
<div class="row g-3">
  
  <!-- Recent Booking Table -->
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Upcoming Bookings</h5>
        <a class="btn btn-link btn-sm p-0" href="{{ route('bookings.index') }}">View All</a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive scrollbar">
          <table class="table table-sm table-striped fs-10 mb-0">
            <thead class="bg-200 text-900">
              <tr>
                <th class="align-middle px-3">Customer</th>
                <th class="align-middle">Hall</th>
                <th class="align-middle">Event Date</th>
                <th class="align-middle">Event Type</th>
                <th class="align-middle text-end px-3">Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse($recentBookings as $b)
                <tr>
                  <td class="align-middle px-3 fw-semi-bold">
                    <a href="{{ route('customers.show', $b->customer_id) }}">{{ $b->customer->full_name }}</a>
                  </td>
                  <td class="align-middle">{{ $b->hall->hall_name ?? '—' }}</td>
                  <td class="align-middle">{{ $b->booking_date->format('M d, Y') }}</td>
                  <td class="align-middle">{{ $b->eventType->event_type_name ?? '—' }}</td>
                  <td class="align-middle text-end px-3">
                    @php
                      $statusColors = [
                        'Draft' => 'secondary',
                        'Reserved' => 'info',
                        'Confirmed' => 'success',
                        'Cancelled' => 'danger',
                        'Rejected' => 'warning'
                      ];
                      $color = $statusColors[$b->booking_status] ?? 'secondary';
                    @endphp
                    <span class="badge badge-subtle-{{ $color }} rounded-pill">{{ $b->booking_status }}</span>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center py-4 text-muted">
                    No upcoming confirmed or reserved bookings.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Operational Quick Links -->
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header bg-light">
        <h5 class="mb-0">Quick Actions</h5>
      </div>
      <div class="card-body">
        <div class="d-grid gap-2">
          <a class="btn btn-outline-primary text-start" href="{{ route('bookings.create') }}">
            <i class="fas fa-plus me-2"></i> Book a Hall / Event
          </a>
          <a class="btn btn-outline-info text-start" href="{{ route('customers.create') }}">
            <i class="fas fa-plus me-2"></i> Register New Customer
          </a>
          <a class="btn btn-outline-success text-start" href="{{ route('finance.payments') }}">
            <i class="fas fa-plus me-2"></i> Record New Payment
          </a>
          <a class="btn btn-outline-secondary text-start" href="{{ route('packages.index') }}">
            <i class="fas fa-plus me-2"></i> Customize Menu Package
          </a>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection
