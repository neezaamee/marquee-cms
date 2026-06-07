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

        <!-- Bookings Section -->
        <li class="nav-item">
          <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
            <div class="col-auto navbar-vertical-label">Bookings & Operations</div>
            <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
          </div>
          
          <a class="nav-link {{ Route::is('branches.*') ? 'active' : '' }}" href="{{ route('branches.index') }}" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-store"></span></span>
              <span class="nav-link-text ps-1">Branches</span>
            </div>
          </a>

          <a class="nav-link" href="#" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-calendar-alt"></span></span>
              <span class="nav-link-text ps-1">Bookings</span>
            </div>
          </a>

          <a class="nav-link {{ Route::is('halls.*') ? 'active' : '' }}" href="{{ route('halls.index') }}" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-hotel"></span></span>
              <span class="nav-link-text ps-1">Halls & Venues</span>
            </div>
          </a>

          <a class="nav-link {{ Route::is('slots.*') ? 'active' : '' }}" href="{{ route('slots.index') }}" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-clock"></span></span>
              <span class="nav-link-text ps-1">Shift Slots</span>
            </div>
          </a>

          <a class="nav-link {{ Route::is('hall-slots.*') ? 'active' : '' }}" href="{{ route('hall-slots.index') }}" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-tasks"></span></span>
              <span class="nav-link-text ps-1">Slot Assignments</span>
            </div>
          </a>

          <a class="nav-link" href="#" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-utensils"></span></span>
              <span class="nav-link-text ps-1">Menus & Catering</span>
            </div>
          </a>
        </li>

        <!-- CRM & Accounts Section -->
        <li class="nav-item">
          <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
            <div class="col-auto navbar-vertical-label">CRM & Finance</div>
            <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
          </div>

          <a class="nav-link" href="#" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-users"></span></span>
              <span class="nav-link-text ps-1">Customers</span>
            </div>
          </a>

          <a class="nav-link" href="#" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-credit-card"></span></span>
              <span class="nav-link-text ps-1">Payments</span>
            </div>
          </a>
        </li>

        <!-- Staff & Management Section -->
        <li class="nav-item">
          <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
            <div class="col-auto navbar-vertical-label">Organization</div>
            <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
          </div>

          <a class="nav-link {{ Route::is('staff.*') ? 'active' : '' }}" href="{{ route('staff.index') }}" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-id-badge"></span></span>
              <span class="nav-link-text ps-1">Staff Management</span>
            </div>
          </a>

          <a class="nav-link {{ Route::is('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-user-shield"></span></span>
              <span class="nav-link-text ps-1">CMS Users</span>
            </div>
          </a>

          <a class="nav-link" href="#" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-chart-line"></span></span>
              <span class="nav-link-text ps-1">Reports & Analytics</span>
            </div>
          </a>
        </li>

        <!-- System Section -->
        <li class="nav-item">
          <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
            <div class="col-auto navbar-vertical-label">System</div>
            <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
          </div>

          <a class="nav-link" href="#" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-cog"></span></span>
              <span class="nav-link-text ps-1">Settings</span>
            </div>
          </a>
        </li>

      </ul>
      
      @if(auth()->user()->isSuperAdmin())
      <ul class="navbar-nav flex-column mb-3">
        <!-- SaaS Admin Section -->
        <li class="nav-item">
          <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
            <div class="col-auto navbar-vertical-label">SaaS Admin</div>
            <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
          </div>

          <a class="nav-link {{ Route::is('marquees.*') ? 'active' : '' }}" href="{{ route('marquees.index') }}" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-building"></span></span>
              <span class="nav-link-text ps-1">Marquees / Tenants</span>
            </div>
          </a>
        </li>
      </ul>
      @endif
    </div>
  </div>
</nav>
