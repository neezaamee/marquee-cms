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
        
        <!-- Core Section -->
        <li class="nav-item">
          <a class="nav-link {{ Route::is('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-chart-pie"></span></span>
              <span class="nav-link-text ps-1">Dashboard</span>
            </div>
          </a>
        </li>

        <!-- Bookings & Operations Category -->
        @php
          $bookingsActive = Route::is('branches.*', 'halls.*', 'slots.*', 'hall-slots.*', 'bookings.*', 'availability.*', 'event-types.*', 'menu-categories.*', 'menu-items.*', 'packages.*', 'extra-services.*');
        @endphp
        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $bookingsActive ? '' : 'collapsed' }}" href="#bookingsCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $bookingsActive ? 'true' : 'false' }}" aria-controls="bookingsCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-ticket-alt"></span></span>
              <span class="nav-link-text ps-1">Bookings & Operations</span>
            </div>
          </a>
          <ul class="nav collapse {{ $bookingsActive ? 'show' : '' }}" id="bookingsCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link {{ Route::is('branches.*') ? 'active' : '' }}" href="{{ route('branches.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Branches</span>
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
              <a class="nav-link {{ Route::is('slots.*') ? 'active' : '' }}" href="{{ route('slots.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Shift Slots</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('hall-slots.*') ? 'active' : '' }}" href="{{ route('hall-slots.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Slot Assignments</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('bookings.*') ? 'active' : '' }}" href="{{ route('bookings.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Bookings</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('availability.*') ? 'active' : '' }}" href="{{ route('availability.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Availability Checker</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('event-types.*') ? 'active' : '' }}" href="{{ route('event-types.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Event Types</span>
                </div>
              </a>
            </li>
            @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_menus'))
              <li class="nav-item">
                <a class="nav-link {{ Route::is('menu-categories.*') ? 'active' : '' }}" href="{{ route('menu-categories.index') }}">
                  <div class="d-flex align-items-center">
                    <span class="nav-link-text ps-1">Menu Categories</span>
                  </div>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link {{ Route::is('menu-items.*') ? 'active' : '' }}" href="{{ route('menu-items.index') }}">
                  <div class="d-flex align-items-center">
                    <span class="nav-link-text ps-1">Menu Items</span>
                  </div>
                </a>
              </li>
            @endif
            @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_packages'))
              <li class="nav-item">
                <a class="nav-link {{ Route::is('packages.*') ? 'active' : '' }}" href="{{ route('packages.index') }}">
                  <div class="d-flex align-items-center">
                    <span class="nav-link-text ps-1">Packages</span>
                  </div>
                </a>
              </li>
            @endif
            @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'))
              <li class="nav-item">
                <a class="nav-link {{ Route::is('extra-services.*') ? 'active' : '' }}" href="{{ route('extra-services.index') }}">
                  <div class="d-flex align-items-center">
                    <span class="nav-link-text ps-1">Add-ons (Services)</span>
                  </div>
                </a>
              </li>
            @endif
          </ul>
        </li>

        <!-- CRM & Finance Category -->
        @php
          $crmActive = Route::is('customers.*');
        @endphp
        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $crmActive ? '' : 'collapsed' }}" href="#crmCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $crmActive ? 'true' : 'false' }}" aria-controls="crmCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-credit-card"></span></span>
              <span class="nav-link-text ps-1">CRM & Finance</span>
            </div>
          </a>
          <ul class="nav collapse {{ $crmActive ? 'show' : '' }}" id="crmCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link {{ Route::is('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Customers</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Payments</span>
                </div>
              </a>
            </li>
          </ul>
        </li>

        <!-- Organization Category -->
        @php
          $orgActive = Route::is('staff.*', 'users.*');
        @endphp
        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $orgActive ? '' : 'collapsed' }}" href="#orgCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $orgActive ? 'true' : 'false' }}" aria-controls="orgCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-sitemap"></span></span>
              <span class="nav-link-text ps-1">Organization</span>
            </div>
          </a>
          <ul class="nav collapse {{ $orgActive ? 'show' : '' }}" id="orgCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link {{ Route::is('staff.*') ? 'active' : '' }}" href="{{ route('staff.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Staff Management</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">CMS Users</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Reports & Analytics</span>
                </div>
              </a>
            </li>
          </ul>
        </li>

        <!-- System Category -->
        <li class="nav-item">
          <a class="nav-link dropdown-indicator collapsed" href="#systemCollapse" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="systemCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-cog"></span></span>
              <span class="nav-link-text ps-1">System</span>
            </div>
          </a>
          <ul class="nav collapse" id="systemCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link" href="#">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Settings</span>
                </div>
              </a>
            </li>
          </ul>
        </li>

        <!-- SaaS Admin Category -->
        @if(auth()->user()->isSuperAdmin())
          @php
            $saasActive = Route::is('marquees.*');
          @endphp
          <li class="nav-item">
            <a class="nav-link dropdown-indicator {{ $saasActive ? '' : 'collapsed' }}" href="#saasCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $saasActive ? 'true' : 'false' }}" aria-controls="saasCollapse">
              <div class="d-flex align-items-center">
                <span class="nav-link-icon"><span class="fas fa-building"></span></span>
                <span class="nav-link-text ps-1">SaaS Admin</span>
              </div>
            </a>
            <ul class="nav collapse {{ $saasActive ? 'show' : '' }}" id="saasCollapse" data-bs-parent="#navbarVerticalNav">
              <li class="nav-item">
                <a class="nav-link {{ Route::is('marquees.*') ? 'active' : '' }}" href="{{ route('marquees.index') }}">
                  <div class="d-flex align-items-center">
                    <span class="nav-link-text ps-1">Marquees / Tenants</span>
                  </div>
                </a>
              </li>
            </ul>
          </li>
        @endif
    </div>
  </div>
</nav>
