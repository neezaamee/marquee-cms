<nav class="navbar navbar-light navbar-vertical navbar-expand-xl">
  <script>
    var navbarStyle = localStorage.getItem("navbarStyle");
    if (navbarStyle && navbarStyle !== 'transparent') {
      document.querySelector('.navbar-vertical').classList.add(`navbar-${navbarStyle}`);
    }
  </script>
  <div class="d-flex align-items-center">
    <div class="toggle-icon-wrapper">
      <button class="btn navbar-toggler-humburger-icon navbar-vertical-toggle" data-bs-toggle="tooltip" data-bs-placement="left" title="Toggle Navigation">
        <span class="navbar-toggle-icon"><span class="toggle-line"></span></span>
      </button>
    </div>
    <a class="navbar-brand" href="{{ route('dashboard') }}">
      <div class="d-flex align-items-center py-3">
        <img class="me-2" src="{{ asset('assets/img/icons/spot-illustrations/falcon.png') }}" alt="Logo" width="50" />
        <span class="font-sans-serif text-primary">M<span class="text-secondary fw-semibold">CMS</span></span>
      </div>
    </a>
  </div>
  <div class="collapse navbar-collapse" id="navbarVerticalCollapse">
    <div class="navbar-vertical-content scrollbar">
      <ul class="navbar-nav flex-column mb-3" id="navbarVerticalNav">
        
        <!-- ========================================== -->
        <!-- DASHBOARD SECTION -->
        <!-- ========================================== -->
        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
          <div class="col-auto navbar-vertical-label">Dashboard</div>
          <div class="col ps-0"><hr class="mb-0 text-300" /></div>
        </div>
        
        <li class="nav-item">
          <a class="nav-link {{ Route::is('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-chart-pie"></span></span>
              <span class="nav-link-text ps-1">Dashboard</span>
            </div>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-muted" href="#!" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-chart-line text-400"></span></span>
              <span class="nav-link-text ps-1">Analytics</span>
              <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
            </div>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-muted" href="#!" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-list-alt text-400"></span></span>
              <span class="nav-link-text ps-1">Quick Stats</span>
              <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
            </div>
          </a>
        </li>

        <!-- ========================================== -->
        <!-- SAAS MANAGEMENT SECTION (Super Admin Only) -->
        <!-- ========================================== -->
        @if(auth()->user()->isSuperAdmin())
        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
          <div class="col-auto navbar-vertical-label">SaaS Management</div>
          <div class="col ps-0"><hr class="mb-0 text-300" /></div>
        </div>

        <!-- Tenants / Marquees -->
        @php
          $tenantsActive = Route::is('marquees.*');
        @endphp
        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $tenantsActive ? '' : 'collapsed' }}" href="#tenantsCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $tenantsActive ? 'true' : 'false' }}" aria-controls="tenantsCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-building"></span></span>
              <span class="nav-link-text ps-1">Tenants / Marquees</span>
            </div>
          </a>
          <ul class="nav collapse {{ $tenantsActive ? 'show' : '' }}" id="tenantsCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link {{ Route::is('marquees.index') ? 'active' : '' }}" href="{{ route('marquees.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">All Marquees</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('marquees.create') ? 'active' : '' }}" href="{{ route('marquees.create') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Add Marquee</span>
                </div>
              </a>
            </li>
          </ul>
        </li>

        <!-- Subscription Management -->
        @php
          $subscriptionsActive = Route::is('subscription-plans.*') || Route::is('plan-features.*') || Route::is('billing-cycles.*') || Route::is('saas-invoices.*') || Route::is('saas-payments.*');
        @endphp
        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $subscriptionsActive ? '' : 'collapsed' }}" href="#subscriptionsCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $subscriptionsActive ? 'true' : 'false' }}" aria-controls="subscriptionsCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-file-invoice-dollar"></span></span>
              <span class="nav-link-text ps-1">Subscription Mgmt</span>
            </div>
          </a>
          <ul class="nav collapse {{ $subscriptionsActive ? 'show' : '' }}" id="subscriptionsCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link {{ Route::is('subscription-plans.*') ? 'active' : '' }}" href="{{ route('subscription-plans.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Subscription Plans</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('plan-features.*') ? 'active' : '' }}" href="{{ route('plan-features.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Plan Features</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('billing-cycles.*') ? 'active' : '' }}" href="{{ route('billing-cycles.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Billing Cycles</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('saas-invoices.*') ? 'active' : '' }}" href="{{ route('saas-invoices.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Invoices</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('saas-payments.*') ? 'active' : '' }}" href="{{ route('saas-payments.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Payment History</span>
                </div>
              </a>
            </li>
          </ul>
        </li>

        <!-- Trial Management -->
        <li class="nav-item">
          <a class="nav-link dropdown-indicator collapsed" href="#trialsCollapse" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="trialsCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-hourglass-half"></span></span>
              <span class="nav-link-text ps-1">Trial Management</span>
            </div>
          </a>
          <ul class="nav collapse" id="trialsCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Trial Accounts</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Expiring Trials</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Trial Conversions</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
          </ul>
        </li>
        @endif

        <!-- ========================================== -->
        <!-- MARQUEE & BRANCH MANAGEMENT SECTION -->
        <!-- ========================================== -->
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'))
        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
          <div class="col-auto navbar-vertical-label">Marquee Management</div>
          <div class="col ps-0"><hr class="mb-0 text-300" /></div>
        </div>

        <!-- Marquees (Detail/Status info) -->
        @if(auth()->user()->isSuperAdmin())
        <li class="nav-item">
          <a class="nav-link dropdown-indicator collapsed" href="#marqueeDetailsCollapse" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="marqueeDetailsCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-hotel"></span></span>
              <span class="nav-link-text ps-1">Marquees</span>
            </div>
          </a>
          <ul class="nav collapse" id="marqueeDetailsCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link {{ Route::is('marquees.index') ? 'active' : '' }}" href="{{ route('marquees.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">All Marquees</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Marquee Details</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Marquee Status</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
          </ul>
        </li>
        @endif

        <!-- Branch Management -->
        @php
          $branchManagementActive = Route::is('branches.*', 'halls.*');
        @endphp
        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $branchManagementActive ? '' : 'collapsed' }}" href="#branchManagementCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $branchManagementActive ? 'true' : 'false' }}" aria-controls="branchManagementCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-map-marker-alt"></span></span>
              <span class="nav-link-text ps-1">Branch Management</span>
            </div>
          </a>
          <ul class="nav collapse {{ $branchManagementActive ? 'show' : '' }}" id="branchManagementCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link {{ Route::is('branches.index') ? 'active' : '' }}" href="{{ route('branches.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">All Branches</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('halls.*') ? 'active' : '' }}" href="{{ route('halls.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Halls & Venues</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Branch Requests</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Branch Categories</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
          </ul>
        </li>
        @endif

        <!-- ========================================== -->
        <!-- USER MANAGEMENT SECTION -->
        <!-- ========================================== -->
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_staff'))
        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
          <div class="col-auto navbar-vertical-label">User Management</div>
          <div class="col ps-0"><hr class="mb-0 text-300" /></div>
        </div>

        @php
          $usersActive = Route::is('users.*');
        @endphp
        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $usersActive ? '' : 'collapsed' }}" href="#usersCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $usersActive ? 'true' : 'false' }}" aria-controls="usersCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-users-cog"></span></span>
              <span class="nav-link-text ps-1">Users</span>
            </div>
          </a>
          <ul class="nav collapse {{ $usersActive ? 'show' : '' }}" id="usersCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link {{ Route::is('users.index') ? 'active' : '' }}" href="{{ route('users.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">All Users</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Add User</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">User Activity</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
          </ul>
        </li>
        <li class="nav-item">
          <a class="nav-link dropdown-indicator collapsed" href="#rolesCollapse" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="rolesCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-shield-alt"></span></span>
              <span class="nav-link-text ps-1">Roles & Permissions</span>
            </div>
          </a>
          <ul class="nav collapse" id="rolesCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Roles</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Permissions</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Access Control</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
          </ul>
        </li>
        @endif

        <!-- ========================================== -->
        <!-- BOOKING & OPERATIONS SECTION -->
        <!-- ========================================== -->
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_bookings'))
        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
          <div class="col-auto navbar-vertical-label">Booking Management</div>
          <div class="col ps-0"><hr class="mb-0 text-300" /></div>
        </div>

        @php
          $bookingsActive = Route::is('bookings.*', 'slots.*', 'hall-slots.*', 'availability.*');
        @endphp
        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $bookingsActive ? '' : 'collapsed' }}" href="#bookingsCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $bookingsActive ? 'true' : 'false' }}" aria-controls="bookingsCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-calendar-alt"></span></span>
              <span class="nav-link-text ps-1">Bookings</span>
            </div>
          </a>
          <ul class="nav collapse {{ $bookingsActive ? 'show' : '' }}" id="bookingsCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link {{ Route::is('bookings.index') ? 'active' : '' }}" href="{{ route('bookings.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">All Bookings</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('availability.index') ? 'active' : '' }}" href="{{ route('availability.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Availability Checker</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('slots.index') ? 'active' : '' }}" href="{{ route('slots.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Shift Slots</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('hall-slots.index') ? 'active' : '' }}" href="{{ route('hall-slots.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Slot Assignments</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Upcoming Events</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Today's Events</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Cancelled Bookings</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
          </ul>
        </li>

        @php
          $eventTypesActive = Route::is('event-types.*');
        @endphp
        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $eventTypesActive ? '' : 'collapsed' }}" href="#eventTypesCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $eventTypesActive ? 'true' : 'false' }}" aria-controls="eventTypesCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-ticket-alt"></span></span>
              <span class="nav-link-text ps-1">Event Types</span>
            </div>
          </a>
          <ul class="nav collapse {{ $eventTypesActive ? 'show' : '' }}" id="eventTypesCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link {{ Route::is('event-types.index') ? 'active' : '' }}" href="{{ route('event-types.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">All Event Types</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Weddings</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Walima</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Engagement</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Birthday</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Corporate Events</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Custom Events</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
          </ul>
        </li>
        @endif

        <!-- ========================================== -->
        <!-- MENU & CATERING MANAGEMENT SECTION -->
        <!-- ========================================== -->
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_menus') || auth()->user()->hasPermission('view_packages'))
        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
          <div class="col-auto navbar-vertical-label">Menu & Catering</div>
          <div class="col ps-0"><hr class="mb-0 text-300" /></div>
        </div>

        @php
          $menuActive = Route::is('menu-categories.*', 'menu-items.*', 'packages.*', 'extra-services.*');
        @endphp
        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $menuActive ? '' : 'collapsed' }}" href="#cateringCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $menuActive ? 'true' : 'false' }}" aria-controls="cateringCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-utensils"></span></span>
              <span class="nav-link-text ps-1">Menus & Packages</span>
            </div>
          </a>
          <ul class="nav collapse {{ $menuActive ? 'show' : '' }}" id="cateringCollapse" data-bs-parent="#navbarVerticalNav">
            @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_menus'))
            <li class="nav-item">
              <a class="nav-link {{ Route::is('menu-categories.index') ? 'active' : '' }}" href="{{ route('menu-categories.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Menu Categories</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('menu-items.index') ? 'active' : '' }}" href="{{ route('menu-items.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Menu Items</span>
                </div>
              </a>
            </li>
            @endif
            @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_packages'))
            <li class="nav-item">
              <a class="nav-link {{ Route::is('packages.index') ? 'active' : '' }}" href="{{ route('packages.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Packages</span>
                </div>
              </a>
            </li>
            @endif
            @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'))
            <li class="nav-item">
              <a class="nav-link {{ Route::is('extra-services.index') ? 'active' : '' }}" href="{{ route('extra-services.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Add-ons (Services)</span>
                </div>
              </a>
            </li>
            @endif
          </ul>
        </li>
        @endif

        <!-- ========================================== -->
        <!-- CUSTOMER MANAGEMENT SECTION -->
        <!-- ========================================== -->
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_bookings'))
        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
          <div class="col-auto navbar-vertical-label">Customer Management</div>
          <div class="col ps-0"><hr class="mb-0 text-300" /></div>
        </div>

        @php
          $customersActive = Route::is('customers.*');
        @endphp
        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $customersActive ? '' : 'collapsed' }}" href="#customersCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $customersActive ? 'true' : 'false' }}" aria-controls="customersCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-users"></span></span>
              <span class="nav-link-text ps-1">Customers</span>
            </div>
          </a>
          <ul class="nav collapse {{ $customersActive ? 'show' : '' }}" id="customersCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link {{ Route::is('customers.index') ? 'active' : '' }}" href="{{ route('customers.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">All Customers</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Customer Feedback</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">VIP Customers</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
          </ul>
        </li>
        @endif

        <!-- ========================================== -->
        <!-- STAFF MANAGEMENT SECTION -->
        <!-- ========================================== -->
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_staff'))
        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
          <div class="col-auto navbar-vertical-label">Staff Management</div>
          <div class="col ps-0"><hr class="mb-0 text-300" /></div>
        </div>

        @php
          $staffActive = Route::is('staff.*');
        @endphp
        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $staffActive ? '' : 'collapsed' }}" href="#staffCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $staffActive ? 'true' : 'false' }}" aria-controls="staffCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-user-tie"></span></span>
              <span class="nav-link-text ps-1">Employees</span>
            </div>
          </a>
          <ul class="nav collapse {{ $staffActive ? 'show' : '' }}" id="staffCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link {{ Route::is('staff.index') ? 'active' : '' }}" href="{{ route('staff.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">All Employees</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Attendance</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Payroll Overview</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
          </ul>
        </li>
        @endif

        <!-- ========================================== -->
        <!-- INVENTORY & PURCHASES SECTION -->
        <!-- ========================================== -->
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_inventory'))
        @php
          $inventoryActive = Route::is('inventory.categories') || Route::is('inventory.units') || Route::is('inventory.brands') || Route::is('inventory.items') || Route::is('inventory.settings') || Route::is('suppliers.*');
          $purchasesActive = Route::is('purchase-orders.*') || Route::is('goods-receipts.*') || Route::is('purchase-invoices.*') || Route::is('purchase-returns.*');
        @endphp
        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
          <div class="col-auto navbar-vertical-label">Inventory & Purchases</div>
          <div class="col ps-0"><hr class="mb-0 text-300" /></div>
        </div>

        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $inventoryActive ? '' : 'collapsed' }}" href="#inventoryCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $inventoryActive ? 'true' : 'false' }}" aria-controls="inventoryCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-boxes"></span></span>
              <span class="nav-link-text ps-1">Inventory</span>
            </div>
          </a>
          <ul class="nav collapse {{ $inventoryActive ? 'show' : '' }}" id="inventoryCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link {{ Route::is('inventory.categories') ? 'active' : '' }}" href="{{ route('inventory.categories') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Categories</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('inventory.units') ? 'active' : '' }}" href="{{ route('inventory.units') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Units of Measure</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('inventory.brands') ? 'active' : '' }}" href="{{ route('inventory.brands') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Brands</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('inventory.items') ? 'active' : '' }}" href="{{ route('inventory.items') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Item Catalog</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Suppliers Directory</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('inventory.settings') ? 'active' : '' }}" href="{{ route('inventory.settings') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Accounting Settings</span>
                </div>
              </a>
            </li>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $purchasesActive ? '' : 'collapsed' }}" href="#purchasesCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $purchasesActive ? 'true' : 'false' }}" aria-controls="purchasesCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-shopping-cart"></span></span>
              <span class="nav-link-text ps-1">Purchases</span>
            </div>
          </a>
          <ul class="nav collapse {{ $purchasesActive ? 'show' : '' }}" id="purchasesCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link {{ Route::is('purchase-orders.*') ? 'active' : '' }}" href="{{ route('purchase-orders.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Purchase Orders</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('goods-receipts.*') ? 'active' : '' }}" href="{{ route('goods-receipts.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Goods Receiving (GRN)</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('purchase-invoices.*') ? 'active' : '' }}" href="{{ route('purchase-invoices.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Purchase Invoices</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('purchase-returns.*') ? 'active' : '' }}" href="{{ route('purchase-returns.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Purchase Returns</span>
                </div>
              </a>
            </li>
          </ul>
        </li>
        @endif

        <!-- ========================================== -->
        <!-- FINANCIAL MANAGEMENT SECTION -->
        <!-- ========================================== -->
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_payments') || auth()->user()->hasPermission('manage_accounting'))
        @php
          $financeActive = Route::is('finance.revenue');
          $transactionsActive = Route::is('finance.payments') || Route::is('finance.security-deposits');
          $accountingActive = Route::is('finance.financial-years') || Route::is('finance.chart-of-accounts') || Route::is('finance.opening-balances') || Route::is('finance.journal-vouchers.*') || Route::is('finance.general-ledger') || Route::is('finance.trial-balance') || Route::is('finance.cash-bank');
        @endphp
        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
          <div class="col-auto navbar-vertical-label">Financial Management</div>
          <div class="col ps-0"><hr class="mb-0 text-300" /></div>
        </div>

        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $financeActive ? '' : 'collapsed' }}" href="#financeCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $financeActive ? 'true' : 'false' }}" aria-controls="financeCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-coins"></span></span>
              <span class="nav-link-text ps-1">Finance</span>
            </div>
          </a>
          <ul class="nav collapse {{ $financeActive ? 'show' : '' }}" id="financeCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link {{ Route::is('finance.revenue') ? 'active' : '' }}" href="{{ route('finance.revenue') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Revenue Dashboard</span>
                </div>
              </a>
            </li>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $transactionsActive ? '' : 'collapsed' }}" href="#transactionsCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $transactionsActive ? 'true' : 'false' }}" aria-controls="transactionsCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-file-invoice"></span></span>
              <span class="nav-link-text ps-1">Transactions</span>
            </div>
          </a>
          <ul class="nav collapse {{ $transactionsActive ? 'show' : '' }}" id="transactionsCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link {{ Route::is('finance.payments') ? 'active' : '' }}" href="{{ route('finance.payments') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Payments Ledger</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('finance.security-deposits') ? 'active' : '' }}" href="{{ route('finance.security-deposits') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Security Deposits</span>
                </div>
              </a>
            </li>
          </ul>
        </li>

        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_accounting'))
        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $accountingActive ? '' : 'collapsed' }}" href="#accountingCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $accountingActive ? 'true' : 'false' }}" aria-controls="accountingCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-calculator"></span></span>
              <span class="nav-link-text ps-1">Accounting</span>
            </div>
          </a>
          <ul class="nav collapse {{ $accountingActive ? 'show' : '' }}" id="accountingCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link {{ Route::is('finance.financial-years') ? 'active' : '' }}" href="{{ route('finance.financial-years') }}">
                <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Financial Years</span></div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('finance.chart-of-accounts') ? 'active' : '' }}" href="{{ route('finance.chart-of-accounts') }}">
                <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Chart of Accounts</span></div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('finance.opening-balances') ? 'active' : '' }}" href="{{ route('finance.opening-balances') }}">
                <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Opening Balances</span></div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('finance.journal-vouchers.*') ? 'active' : '' }}" href="{{ route('finance.journal-vouchers.index') }}">
                <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Journal Vouchers</span></div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('finance.general-ledger') ? 'active' : '' }}" href="{{ route('finance.general-ledger') }}">
                <div class="d-flex align-items-center"><span class="nav-link-text ps-1">General Ledger</span></div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('finance.trial-balance') ? 'active' : '' }}" href="{{ route('finance.trial-balance') }}">
                <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Trial Balance</span></div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('finance.cash-bank') ? 'active' : '' }}" href="{{ route('finance.cash-bank') }}">
                <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Cash & Bank Accounts</span></div>
              </a>
            </li>
          </ul>
        </li>
        @endif
        @endif

        <!-- ========================================== -->
        <!-- REPORTS & ANALYTICS SECTION -->
        <!-- ========================================== -->
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_reports'))
        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
          <div class="col-auto navbar-vertical-label">Reports & Analytics</div>
          <div class="col ps-0"><hr class="mb-0 text-300" /></div>
        </div>

        @php
          $reportsActive = Route::is('bookings.report');
        @endphp
        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $reportsActive ? '' : 'collapsed' }}" href="#reportsCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $reportsActive ? 'true' : 'false' }}" aria-controls="reportsCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-file-alt"></span></span>
              <span class="nav-link-text ps-1">Reports</span>
            </div>
          </a>
          <ul class="nav collapse {{ $reportsActive ? 'show' : '' }}" id="reportsCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link {{ Route::is('bookings.report') ? 'active' : '' }}" href="{{ route('bookings.report') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Booking Reports</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Revenue Reports</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Customer Reports</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Inventory Reports</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Staff Reports</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link dropdown-indicator collapsed" href="#analyticsCollapse" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="analyticsCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-chart-bar"></span></span>
              <span class="nav-link-text ps-1">Analytics</span>
            </div>
          </a>
          <ul class="nav collapse" id="analyticsCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Business Growth</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Occupancy Rate</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Top Performing Marquees</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
          </ul>
        </li>
        @endif

        <!-- ========================================== -->
        <!-- COMMUNICATION CENTER SECTION -->
        <!-- ========================================== -->
        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
          <div class="col-auto navbar-vertical-label">Communication</div>
          <div class="col ps-0"><hr class="mb-0 text-300" /></div>
        </div>

        <li class="nav-item">
          <a class="nav-link dropdown-indicator collapsed" href="#notificationsCollapse" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="notificationsCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-bell"></span></span>
              <span class="nav-link-text ps-1">Notifications</span>
            </div>
          </a>
          <ul class="nav collapse" id="notificationsCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Push Notifications</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">SMS Templates</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Email Templates</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link dropdown-indicator collapsed" href="#supportCollapse" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="supportCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-headset"></span></span>
              <span class="nav-link-text ps-1">Support</span>
            </div>
          </a>
          <ul class="nav collapse" id="supportCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Support Tickets</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Live Chat</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Contact Requests</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
          </ul>
        </li>

        <!-- ========================================== -->
        <!-- CONTENT MANAGEMENT SECTION -->
        <!-- ========================================== -->
        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
          <div class="col-auto navbar-vertical-label">Content Management</div>
          <div class="col ps-0"><hr class="mb-0 text-300" /></div>
        </div>

        <li class="nav-item">
          <a class="nav-link dropdown-indicator collapsed" href="#cmsCollapse" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="cmsCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-pager"></span></span>
              <span class="nav-link-text ps-1">Website CMS</span>
            </div>
          </a>
          <ul class="nav collapse" id="cmsCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Pages</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Blogs</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Testimonials</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">FAQ</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link dropdown-indicator collapsed" href="#mediaCollapse" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="mediaCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-images"></span></span>
              <span class="nav-link-text ps-1">Media Library</span>
            </div>
          </a>
          <ul class="nav collapse" id="mediaCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Images</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Documents</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Videos</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
          </ul>
        </li>

        <!-- ========================================== -->
        <!-- SYSTEM ADMINISTRATION SECTION (Super Admin Only) -->
        <!-- ========================================== -->
        @if(auth()->user()->isSuperAdmin())
        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
          <div class="col-auto navbar-vertical-label">System Administration</div>
          <div class="col ps-0"><hr class="mb-0 text-300" /></div>
        </div>

        <li class="nav-item">
          <a class="nav-link dropdown-indicator collapsed" href="#systemSettingsCollapse" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="systemSettingsCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-sliders-h"></span></span>
              <span class="nav-link-text ps-1">System Settings</span>
            </div>
          </a>
          <ul class="nav collapse" id="systemSettingsCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">General Settings</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Business Settings</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Currency Settings</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Tax Settings</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link dropdown-indicator collapsed" href="#securityCollapse" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="securityCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-lock"></span></span>
              <span class="nav-link-text ps-1">Security</span>
            </div>
          </a>
          <ul class="nav collapse" id="securityCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Login Logs</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Activity Logs</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Audit Logs</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link dropdown-indicator collapsed" href="#integrationsCollapse" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="integrationsCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-plug"></span></span>
              <span class="nav-link-text ps-1">Integrations</span>
            </div>
          </a>
          <ul class="nav collapse" id="integrationsCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Payment Gateways</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">SMS Gateways</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">WhatsApp API</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Email Services</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
          </ul>
        </li>

        <!-- ========================================== -->
        <!-- PLATFORM MONITORING SECTION (Super Admin Only) -->
        <!-- ========================================== -->
        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
          <div class="col-auto navbar-vertical-label">Monitoring</div>
          <div class="col ps-0"><hr class="mb-0 text-300" /></div>
        </div>

        <li class="nav-item">
          <a class="nav-link dropdown-indicator collapsed" href="#monitoringCollapse" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="monitoringCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-heartbeat"></span></span>
              <span class="nav-link-text ps-1">System Health</span>
            </div>
          </a>
          <ul class="nav collapse" id="monitoringCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Server Status</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Queue Monitoring</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Error Logs</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link dropdown-indicator collapsed" href="#backupsCollapse" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="backupsCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-database"></span></span>
              <span class="nav-link-text ps-1">Backup Mgmt</span>
            </div>
          </a>
          <ul class="nav collapse" id="backupsCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Manual Backup</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Scheduled Backups</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Restore Backup</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
          </ul>
        </li>

        <!-- ========================================== -->
        <!-- DEVELOPMENT TOOLS SECTION (Super Admin Only) -->
        <!-- ========================================== -->
        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
          <div class="col-auto navbar-vertical-label">Developer Tools</div>
          <div class="col ps-0"><hr class="mb-0 text-300" /></div>
        </div>

        <li class="nav-item">
          <a class="nav-link dropdown-indicator collapsed" href="#devToolsCollapse" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="devToolsCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-terminal"></span></span>
              <span class="nav-link-text ps-1">Developer Tools</span>
            </div>
          </a>
          <ul class="nav collapse" id="devToolsCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Cache Management</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Queue Management</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted" href="#!">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">System Info</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
          </ul>
        </li>
        @endif

        <!-- ========================================== -->
        <!-- PROFILE & UTILITIES -->
        <!-- ========================================== -->
        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
          <div class="col-auto navbar-vertical-label">Session</div>
          <div class="col ps-0"><hr class="mb-0 text-300" /></div>
        </div>

        <li class="nav-item">
          <a class="nav-link text-muted" href="#!" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-user text-400"></span></span>
              <span class="nav-link-text ps-1">My Profile</span>
              <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
            </div>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-muted" href="#!" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-key text-400"></span></span>
              <span class="nav-link-text ps-1">Change Password</span>
              <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
            </div>
          </a>
        </li>
        <li class="nav-item">
          <form method="POST" action="{{ route('logout') }}" id="logout-form-sidebar" class="d-none">
            @csrf
          </form>
          <a class="nav-link" href="#!" onclick="event.preventDefault(); document.getElementById('logout-form-sidebar').submit();" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-sign-out-alt"></span></span>
              <span class="nav-link-text ps-1">Logout</span>
            </div>
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>
