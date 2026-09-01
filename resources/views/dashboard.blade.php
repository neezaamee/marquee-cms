@extends('layouts.admin')

@section('title', $isSuperAdmin ? 'Executive Dashboard' : 'Dashboard')

@section('content')
@if(session('warning'))
<div class="alert alert-warning border-2 d-flex align-items-center mb-3 alert-dismissible fade show shadow-sm" role="alert">
  <span class="fas fa-exclamation-triangle me-2"></span>
  <p class="mb-0 flex-1">{{ session('warning') }}</p>
  <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('success'))
<div class="alert alert-success border-2 d-flex align-items-center mb-3 alert-dismissible fade show shadow-sm" role="alert">
  <span class="fas fa-check-circle me-2"></span>
  <p class="mb-0 flex-1">{{ session('success') }}</p>
  <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if($isSuperAdmin)
    <!-- Super Admin SaaS Executive Dashboard -->
    <livewire:super-admin.super-admin-dashboard />
@else
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
            <a class="btn btn-warning" href="{{ route('setup.wizard') }}">Open Setup Wizard <span class="fas fa-magic ms-1"></span></a>
          </div>
        </div>
        
        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-5 g-3 mt-3">
          <!-- Task 1: Marquee Info -->
          <div class="col">
            <div class="p-3 bg-body border border-translucent rounded-3 text-center position-relative h-100">
              <div class="avatar avatar-2xl mb-2 mx-auto">
                <span class="avatar-name rounded-circle {{ ($setupChecklist['marquee_info'] ?? false) ? 'bg-subtle-success text-success' : 'bg-subtle-secondary text-secondary' }}">
                  <i class="fas {{ ($setupChecklist['marquee_info'] ?? false) ? 'fa-check' : 'fa-building' }}"></i>
                </span>
              </div>
              <h6 class="mb-1 text-900 fs-11">Business Profile</h6>
              <span class="badge badge-subtle-{{ ($setupChecklist['marquee_info'] ?? false) ? 'success' : 'secondary' }} rounded-pill" style="font-size: 8px;">
                {{ ($setupChecklist['marquee_info'] ?? false) ? 'Completed ✔' : 'Pending ✖' }}
              </span>
              <a class="stretched-link" href="{{ route('setup.wizard', ['step' => 1]) }}"></a>
            </div>
          </div>

          <!-- Task 2: Main Branch -->
          <div class="col">
            <div class="p-3 bg-body border border-translucent rounded-3 text-center position-relative h-100">
              <div class="avatar avatar-2xl mb-2 mx-auto">
                <span class="avatar-name rounded-circle {{ ($setupChecklist['branch'] ?? false) ? 'bg-subtle-success text-success' : 'bg-subtle-secondary text-secondary' }}">
                  <i class="fas {{ ($setupChecklist['branch'] ?? false) ? 'fa-check' : 'fa-map-marker-alt' }}"></i>
                </span>
              </div>
              <h6 class="mb-1 text-900 fs-11">Main Branch</h6>
              <span class="badge badge-subtle-{{ ($setupChecklist['branch'] ?? false) ? 'success' : 'secondary' }} rounded-pill" style="font-size: 8px;">
                {{ ($setupChecklist['branch'] ?? false) ? 'Completed ✔' : 'Pending ✖' }}
              </span>
              <a class="stretched-link" href="{{ route('setup.wizard', ['step' => 2]) }}"></a>
            </div>
          </div>

          <!-- Task 3: Branch Config -->
          <div class="col">
            <div class="p-3 bg-body border border-translucent rounded-3 text-center position-relative h-100">
              <div class="avatar avatar-2xl mb-2 mx-auto">
                <span class="avatar-name rounded-circle {{ ($setupChecklist['branch_config'] ?? false) ? 'bg-subtle-success text-success' : 'bg-subtle-secondary text-secondary' }}">
                  <i class="fas {{ ($setupChecklist['branch_config'] ?? false) ? 'fa-check' : 'fa-cog' }}"></i>
                </span>
              </div>
              <h6 class="mb-1 text-900 fs-11">Branch Config</h6>
              <span class="badge badge-subtle-{{ ($setupChecklist['branch_config'] ?? false) ? 'success' : 'secondary' }} rounded-pill" style="font-size: 8px;">
                {{ ($setupChecklist['branch_config'] ?? false) ? 'Completed ✔' : 'Pending ✖' }}
              </span>
              <a class="stretched-link" href="{{ route('setup.wizard', ['step' => 3]) }}"></a>
            </div>
          </div>

          <!-- Task 4: Halls -->
          <div class="col">
            <div class="p-3 bg-body border border-translucent rounded-3 text-center position-relative h-100">
              <div class="avatar avatar-2xl mb-2 mx-auto">
                <span class="avatar-name rounded-circle {{ ($setupChecklist['halls'] ?? false) ? 'bg-subtle-success text-success' : 'bg-subtle-secondary text-secondary' }}">
                  <i class="fas {{ ($setupChecklist['halls'] ?? false) ? 'fa-check' : 'fa-hotel' }}"></i>
                </span>
              </div>
              <h6 class="mb-1 text-900 fs-11">Halls Setup</h6>
              <span class="badge badge-subtle-{{ ($setupChecklist['halls'] ?? false) ? 'success' : 'secondary' }} rounded-pill" style="font-size: 8px;">
                {{ ($setupChecklist['halls'] ?? false) ? 'Completed ✔' : 'Pending ✖' }}
              </span>
              <a class="stretched-link" href="{{ route('setup.wizard', ['step' => 4]) }}"></a>
            </div>
          </div>

          <!-- Task 5: Departments -->
          <div class="col">
            <div class="p-3 bg-body border border-translucent rounded-3 text-center position-relative h-100">
              <div class="avatar avatar-2xl mb-2 mx-auto">
                <span class="avatar-name rounded-circle {{ ($setupChecklist['departments'] ?? false) ? 'bg-subtle-success text-success' : 'bg-subtle-secondary text-secondary' }}">
                  <i class="fas {{ ($setupChecklist['departments'] ?? false) ? 'fa-check' : 'fa-sitemap' }}"></i>
                </span>
              </div>
              <h6 class="mb-1 text-900 fs-11">Departments</h6>
              <span class="badge badge-subtle-{{ ($setupChecklist['departments'] ?? false) ? 'success' : 'secondary' }} rounded-pill" style="font-size: 8px;">
                {{ ($setupChecklist['departments'] ?? false) ? 'Completed ✔' : 'Pending ✖' }}
              </span>
              <a class="stretched-link" href="{{ route('setup.wizard', ['step' => 5]) }}"></a>
            </div>
          </div>

          <!-- Task 6: Booking Masters -->
          <div class="col">
            <div class="p-3 bg-body border border-translucent rounded-3 text-center position-relative h-100">
              <div class="avatar avatar-2xl mb-2 mx-auto">
                <span class="avatar-name rounded-circle {{ ($setupChecklist['booking_masters'] ?? false) ? 'bg-subtle-success text-success' : 'bg-subtle-secondary text-secondary' }}">
                  <i class="fas {{ ($setupChecklist['booking_masters'] ?? false) ? 'fa-check' : 'fa-ticket-alt' }}"></i>
                </span>
              </div>
              <h6 class="mb-1 text-900 fs-11">Booking Masters</h6>
              <span class="badge badge-subtle-{{ ($setupChecklist['booking_masters'] ?? false) ? 'success' : 'secondary' }} rounded-pill" style="font-size: 8px;">
                {{ ($setupChecklist['booking_masters'] ?? false) ? 'Completed ✔' : 'Pending ✖' }}
              </span>
              <a class="stretched-link" href="{{ route('setup.wizard', ['step' => 6]) }}"></a>
            </div>
          </div>

          <!-- Task 7: Menu & Packages -->
          <div class="col">
            <div class="p-3 bg-body border border-translucent rounded-3 text-center position-relative h-100">
              <div class="avatar avatar-2xl mb-2 mx-auto">
                <span class="avatar-name rounded-circle {{ ($setupChecklist['menu_packages'] ?? false) ? 'bg-subtle-success text-success' : 'bg-subtle-secondary text-secondary' }}">
                  <i class="fas {{ ($setupChecklist['menu_packages'] ?? false) ? 'fa-check' : 'fa-utensils' }}"></i>
                </span>
              </div>
              <h6 class="mb-1 text-900 fs-11">Menu & Packages</h6>
              <span class="badge badge-subtle-{{ ($setupChecklist['menu_packages'] ?? false) ? 'success' : 'secondary' }} rounded-pill" style="font-size: 8px;">
                {{ ($setupChecklist['menu_packages'] ?? false) ? 'Completed ✔' : 'Pending ✖' }}
              </span>
              <a class="stretched-link" href="{{ route('setup.wizard', ['step' => 7]) }}"></a>
            </div>
          </div>

          <!-- Task 8: Inventory -->
          <div class="col">
            <div class="p-3 bg-body border border-translucent rounded-3 text-center position-relative h-100">
              <div class="avatar avatar-2xl mb-2 mx-auto">
                <span class="avatar-name rounded-circle {{ ($setupChecklist['inventory'] ?? false) ? 'bg-subtle-success text-success' : 'bg-subtle-secondary text-secondary' }}">
                  <i class="fas {{ ($setupChecklist['inventory'] ?? false) ? 'fa-check' : 'fa-boxes' }}"></i>
                </span>
              </div>
              <h6 class="mb-1 text-900 fs-11">Inventory</h6>
              <span class="badge badge-subtle-{{ ($setupChecklist['inventory'] ?? false) ? 'success' : 'secondary' }} rounded-pill" style="font-size: 8px;">
                {{ ($setupChecklist['inventory'] ?? false) ? 'Completed ✔' : 'Pending ✖' }}
              </span>
              <a class="stretched-link" href="{{ route('setup.wizard', ['step' => 8]) }}"></a>
            </div>
          </div>

          <!-- Task 9: Finance -->
          <div class="col">
            <div class="p-3 bg-body border border-translucent rounded-3 text-center position-relative h-100">
              <div class="avatar avatar-2xl mb-2 mx-auto">
                <span class="avatar-name rounded-circle {{ ($setupChecklist['finance'] ?? false) ? 'bg-subtle-success text-success' : 'bg-subtle-secondary text-secondary' }}">
                  <i class="fas {{ ($setupChecklist['finance'] ?? false) ? 'fa-check' : 'fa-dollar-sign' }}"></i>
                </span>
              </div>
              <h6 class="mb-1 text-900 fs-11">Finance Config</h6>
              <span class="badge badge-subtle-{{ ($setupChecklist['finance'] ?? false) ? 'success' : 'secondary' }} rounded-pill" style="font-size: 8px;">
                {{ ($setupChecklist['finance'] ?? false) ? 'Completed ✔' : 'Pending ✖' }}
              </span>
              <a class="stretched-link" href="{{ route('setup.wizard', ['step' => 9]) }}"></a>
            </div>
          </div>
        </div>
      </div>
    </div>
    @else
    <!-- Business Owner Live Dashboard -->
    <livewire:owner.business-owner-dashboard />
    @endif
@endif
@endsection
