@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<!-- Welcome Header Banner -->
<div class="card mb-3">
  <div class="card-body overflow-hidden p-lg-6">
    <div class="row align-items-center">
      <div class="col-lg-6">
        <img class="img-fluid" src="{{ asset('assets/img/icons/spot-illustrations/21.png') }}" alt="Welcome illustration" width="350" />
      </div>
      <div class="col-lg-6 ps-lg-4 my-5 text-center text-lg-start">
        <h3 class="text-primary">Welcome to Marquee CMS!</h3>
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
        <div class="display-4 fs-4 mb-2 fw-normal font-sans-serif text-warning">142</div>
        <a class="fw-semi-bold fs-10" href="#!">View details <span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
      </div>
    </div>
  </div>

  <!-- Venues / Halls -->
  <div class="col-sm-6 col-md-3">
    <div class="card overflow-hidden" style="min-width: 12rem">
      <div class="bg-holder bg-card" style="background-image:url({{ asset('assets/img/icons/spot-illustrations/corner-2.png') }});"></div>
      <div class="card-body position-relative">
        <h6 class="text-uppercase text-600">Active Halls</h6>
        <div class="display-4 fs-4 mb-2 fw-normal font-sans-serif text-info">4</div>
        <a class="fw-semi-bold fs-10" href="#!">Manage venues <span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
      </div>
    </div>
  </div>

  <!-- Menu Packages -->
  <div class="col-sm-6 col-md-3">
    <div class="card overflow-hidden" style="min-width: 12rem">
      <div class="bg-holder bg-card" style="background-image:url({{ asset('assets/img/icons/spot-illustrations/corner-3.png') }});"></div>
      <div class="card-body position-relative">
        <h6 class="text-uppercase text-600">Menu Packages</h6>
        <div class="display-4 fs-4 mb-2 fw-normal font-sans-serif text-success">8</div>
        <a class="fw-semi-bold fs-10" href="#!">View menus <span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
      </div>
    </div>
  </div>

  <!-- Monthly Revenue -->
  <div class="col-sm-6 col-md-3">
    <div class="card overflow-hidden" style="min-width: 12rem">
      <div class="bg-holder bg-card" style="background-image:url({{ asset('assets/img/icons/spot-illustrations/corner-4.png') }});"></div>
      <div class="card-body position-relative">
        <h6 class="text-uppercase text-600">Monthly Revenue</h6>
        <div class="display-4 fs-4 mb-2 fw-normal font-sans-serif text-primary">$45.2K</div>
        <a class="fw-semi-bold fs-10" href="#!">View transactions <span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
      </div>
    </div>
  </div>

</div>

<!-- Quick Actions & Recent Bookings placeholder -->
<div class="row g-3">
  
  <!-- Recent Booking Table Placeholder -->
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Upcoming Bookings</h5>
        <a class="btn btn-link btn-sm p-0" href="#!">View All</a>
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
              <tr>
                <td class="align-middle px-3 fw-semi-bold">Muhammad Ali</td>
                <td class="align-middle">Grand Royal Ballroom</td>
                <td class="align-middle">June 20, 2026</td>
                <td class="align-middle">Wedding reception</td>
                <td class="align-middle text-end px-3">
                  <span class="badge badge-subtle-success rounded-pill">Confirmed</span>
                </td>
              </tr>
              <tr>
                <td class="align-middle px-3 fw-semi-bold">Ayesha Khan</td>
                <td class="align-middle">Crystal Banquet Hall</td>
                <td class="align-middle">June 25, 2026</td>
                <td class="align-middle">Birthday party</td>
                <td class="align-middle text-end px-3">
                  <span class="badge badge-subtle-warning rounded-pill">Pending Payment</span>
                </td>
              </tr>
              <tr>
                <td class="align-middle px-3 fw-semi-bold">Fatima Sajid</td>
                <td class="align-middle">Grand Royal Ballroom</td>
                <td class="align-middle">July 02, 2026</td>
                <td class="align-middle">Corporate Seminar</td>
                <td class="align-middle text-end px-3">
                  <span class="badge badge-subtle-primary rounded-pill">Hold</span>
                </td>
              </tr>
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
          <a class="btn btn-outline-primary text-start" href="#!">
            <i class="fas fa-plus me-2"></i> Book a Hall / Event
          </a>
          <a class="btn btn-outline-info text-start" href="#!">
            <i class="fas fa-plus me-2"></i> Register New Customer
          </a>
          <a class="btn btn-outline-success text-start" href="#!">
            <i class="fas fa-plus me-2"></i> Record New Payment
          </a>
          <a class="btn btn-outline-secondary text-start" href="#!">
            <i class="fas fa-plus me-2"></i> Customize Menu Package
          </a>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection
