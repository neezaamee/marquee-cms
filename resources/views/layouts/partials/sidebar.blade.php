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

        <!-- Global Default Data Management -->
        <li class="nav-item">
          <a class="nav-link {{ Route::is('super-admin.global-defaults') ? 'active' : '' }}" href="{{ route('super-admin.global-defaults') }}" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-globe text-primary"></span></span>
              <span class="nav-link-text ps-1">Global Default Data</span>
            </div>
          </a>
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
        <!-- SALES & BOOKINGS SECTION -->
        <!-- ========================================== -->
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_bookings'))
        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
          <div class="col-auto navbar-vertical-label">Sales & Bookings</div>
          <div class="col ps-0"><hr class="mb-0 text-300" /></div>
        </div>

        <!-- Leads / Inquiries -->
        <li class="nav-item">
          <a class="nav-link text-muted" href="#!" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-filter text-400"></span></span>
              <span class="nav-link-text ps-1">Leads / Inquiries</span>
              <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
            </div>
          </a>
        </li>

        <!-- Customers dropdown -->
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
              <a class="nav-link {{ Route::is('customers.referral-analytics') ? 'active' : '' }}" href="{{ route('customers.referral-analytics') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Referral Analytics</span>
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

        <!-- Availability Checker -->
        <li class="nav-item">
          <a class="nav-link {{ Route::is('availability.index') ? 'active' : '' }}" href="{{ route('availability.index') }}" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-calendar-check text-success"></span></span>
              <span class="nav-link-text ps-1">Availability Checker</span>
            </div>
          </a>
        </li>

        <!-- Visual Booking Calendar -->
        <li class="nav-item">
          @php
            $calendarActive = Route::is('bookings.calendar');
          @endphp
          <a class="nav-link {{ $calendarActive ? 'active' : '' }}" href="{{ route('bookings.calendar') }}" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-calendar text-primary"></span></span>
              <span class="nav-link-text ps-1">Booking Calendar</span>
            </div>
          </a>
        </li>

        <!-- Bookings dropdown -->
        @php
          $bookingsActive = Route::is('bookings.index', 'bookings.show', 'bookings.edit', 'bookings.create');
        @endphp
        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $bookingsActive ? '' : 'collapsed' }}" href="#bookingsCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $bookingsActive ? 'true' : 'false' }}" aria-controls="bookingsCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-book"></span></span>
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
        @endif

        <!-- ========================================== -->
        <!-- CATERING & KITCHEN SECTION -->
        <!-- ========================================== -->
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_menus') || auth()->user()->hasPermission('view_packages'))
        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
          <div class="col-auto navbar-vertical-label">Catering & Kitchen</div>
          <div class="col ps-0"><hr class="mb-0 text-300" /></div>
        </div>

        <!-- Menus & Packages dropdown -->
        @php
          $cateringActive = Route::is('menu-categories.*', 'menu-items.*', 'recipes.index', 'packages.*');
        @endphp
        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $cateringActive ? '' : 'collapsed' }}" href="#cateringCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $cateringActive ? 'true' : 'false' }}" aria-controls="cateringCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-utensils text-primary"></span></span>
              <span class="nav-link-text ps-1">Menus & Packages</span>
            </div>
          </a>
          <ul class="nav collapse {{ $cateringActive ? 'show' : '' }}" id="cateringCollapse" data-bs-parent="#navbarVerticalNav">
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
            <li class="nav-item">
              <a class="nav-link {{ Route::is('recipes.index') ? 'active' : '' }}" href="{{ route('recipes.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Recipe & Ingredient Calc</span>
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
          </ul>
        </li>

        <!-- Kitchen Production -->
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_accounting'))
        <li class="nav-item">
          <a class="nav-link {{ Route::is('departments.production') ? 'active' : '' }}" href="{{ route('departments.production') }}">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-fire text-danger"></span></span>
              <span class="nav-link-text ps-1">Kitchen Production</span>
            </div>
          </a>
        </li>
        @endif
        @endif

        <!-- ========================================== -->
        <!-- OPERATIONS SECTION -->
        <!-- ========================================== -->
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_bookings') || auth()->user()->hasPermission('manage_staff') || auth()->user()->hasPermission('manage_accounting'))
        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
          <div class="col-auto navbar-vertical-label">Operations</div>
          <div class="col ps-0"><hr class="mb-0 text-300" /></div>
        </div>

        <!-- Event Day Checklist -->
        <li class="nav-item">
          <a class="nav-link {{ Route::is('operations.checklists') ? 'active' : '' }}" href="{{ route('operations.checklists') }}">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-clipboard-list text-info"></span></span>
              <span class="nav-link-text ps-1">Event Day Checklist</span>
            </div>
          </a>
        </li>

        <!-- Staff Attendance Register -->
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_staff'))
        <li class="nav-item">
          <a class="nav-link {{ Route::is('staff.attendance') ? 'active' : '' }}" href="{{ route('staff.attendance') }}">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-calendar-check text-warning"></span></span>
              <span class="nav-link-text ps-1">Staff Attendance</span>
            </div>
          </a>
        </li>
        @endif

        <!-- Department Operations dropdown -->
        @php
          $deptActive = Route::is('departments.*') && !Route::is('departments.production');
        @endphp
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_accounting'))
        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $deptActive ? '' : 'collapsed' }}" href="#departmentsCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $deptActive ? 'true' : 'false' }}" aria-controls="departmentsCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-sitemap"></span></span>
              <span class="nav-link-text ps-1">Department Operations</span>
            </div>
          </a>
          <ul class="nav collapse {{ $deptActive ? 'show' : '' }}" id="departmentsCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link {{ Route::is('departments.dashboard') ? 'active' : '' }}" href="{{ route('departments.dashboard') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Overview Dashboard</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('departments.index') ? 'active' : '' }}" href="{{ route('departments.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Department Master</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('departments.employees') ? 'active' : '' }}" href="{{ route('departments.employees') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Department Staff Roster</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('departments.attendance') ? 'active' : '' }}" href="{{ route('departments.attendance') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Attendance Register</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('departments.requests') ? 'active' : '' }}" href="{{ route('departments.requests') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Stock Requisitions</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('departments.issue') ? 'active' : '' }}" href="{{ route('departments.issue') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Stock Issue / Dispatch</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('departments.returns') ? 'active' : '' }}" href="{{ route('departments.returns') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Stock Returns</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('departments.ledger') ? 'active' : '' }}" href="{{ route('departments.ledger') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Stock Ledgers</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('departments.reports') ? 'active' : '' }}" href="{{ route('departments.reports') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Department Reports</span>
                </div>
              </a>
            </li>
          </ul>
        </li>
        @endif
        @endif

        <!-- ========================================== -->
        <!-- PROCUREMENT & INVENTORY SECTION -->
        <!-- ========================================== -->
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_inventory'))
        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
          <div class="col-auto navbar-vertical-label">Procurement & Inventory</div>
          <div class="col ps-0"><hr class="mb-0 text-300" /></div>
        </div>

        <!-- Inventory dropdown -->
        @php
          $inventoryActive = Route::is('inventory.categories') || Route::is('inventory.units') || Route::is('inventory.brands') || Route::is('inventory.items') || Route::is('inventory.stock') || Route::is('inventory.stock-takes.index') || Route::is('inventory.stock-ledger');
        @endphp
        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $inventoryActive ? '' : 'collapsed' }}" href="#inventoryCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $inventoryActive ? 'true' : 'false' }}" aria-controls="inventoryCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-boxes text-success"></span></span>
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
              <a class="nav-link {{ Route::is('inventory.stock') ? 'active' : '' }}" href="{{ route('inventory.stock') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Stock View</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('inventory.stock-ledger') ? 'active' : '' }}" href="{{ route('inventory.stock-ledger') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Stock Ledger</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('inventory.stock-takes.index') ? 'active' : '' }}" href="{{ route('inventory.stock-takes.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Stock Adjustments</span>
                </div>
              </a>
            </li>
          </ul>
        </li>

        <!-- Purchases / Procurement dropdown -->
        @php
          $purchasesActive = Route::is('purchase-orders.*') || Route::is('goods-receipts.*') || Route::is('purchase-invoices.*') || Route::is('purchase-returns.*') || Route::is('suppliers.*');
        @endphp
        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $purchasesActive ? '' : 'collapsed' }}" href="#purchasesCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $purchasesActive ? 'true' : 'false' }}" aria-controls="purchasesCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-shopping-cart text-warning"></span></span>
              <span class="nav-link-text ps-1">Purchases</span>
            </div>
          </a>
          <ul class="nav collapse {{ $purchasesActive ? 'show' : '' }}" id="purchasesCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link {{ Route::is('suppliers.index') || Route::is('suppliers.ledger') ? 'active' : '' }}" href="{{ route('suppliers.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Suppliers Directory</span>
                </div>
              </a>
            </li>
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
        <!-- VENDORS & PARTNERS SECTION -->
        <!-- ========================================== -->
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_bookings') || auth()->user()->hasPermission('manage_settings'))
        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
          <div class="col-auto navbar-vertical-label">Vendors & Partners</div>
          <div class="col ps-0"><hr class="mb-0 text-300" /></div>
        </div>

        <!-- Service Providers dropdown -->
        @php
          $vendorActive = Route::is('vendors.*', 'vendor-services.*', 'vendor-agreements.*', 'vendor-sales.*', 'vendor-ledger.*', 'vendor-settlements.*', 'vendor-reports.*') && !Route::is('vendors.index');
          $vendorMainActive = Route::is('vendors.*', 'vendor-services.*', 'vendor-agreements.*', 'vendor-sales.*', 'vendor-ledger.*', 'vendor-settlements.*', 'vendor-reports.*');
        @endphp
        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $vendorMainActive ? '' : 'collapsed' }}" href="#vendorManagementCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $vendorMainActive ? 'true' : 'false' }}" aria-controls="vendorManagementCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-handshake text-warning"></span></span>
              <span class="nav-link-text ps-1">Vendors Directory</span>
            </div>
          </a>
          <ul class="nav collapse {{ $vendorMainActive ? 'show' : '' }}" id="vendorManagementCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link {{ Route::is('vendors.dashboard') ? 'active' : '' }}" href="{{ route('vendors.dashboard') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Dashboard</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('vendors.index') || Route::is('vendors.show') ? 'active' : '' }}" href="{{ route('vendors.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">All Vendors</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('vendor-services.*') ? 'active' : '' }}" href="{{ route('vendor-services.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Services Catalog</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('vendor-agreements.*') ? 'active' : '' }}" href="{{ route('vendor-agreements.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Agreements</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('vendor-sales.*') ? 'active' : '' }}" href="{{ route('vendor-sales.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Provider Sales</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('vendor-ledger.*') ? 'active' : '' }}" href="{{ route('vendor-ledger.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Ledger</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('vendor-settlements.*') ? 'active' : '' }}" href="{{ route('vendor-settlements.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Settlements</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('vendor-reports.*') ? 'active' : '' }}" href="{{ route('vendor-reports.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Reports</span>
                </div>
              </a>
            </li>
          </ul>
        </li>
        @endif

        <!-- ========================================== -->
        <!-- FINANCE & ACCOUNTING SECTION -->
        <!-- ========================================== -->
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_payments') || auth()->user()->hasPermission('manage_accounting') || auth()->user()->hasPermission('view_expenses'))
        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
          <div class="col-auto navbar-vertical-label">Finance & Accounting</div>
          <div class="col ps-0"><hr class="mb-0 text-300" /></div>
        </div>

        <!-- Financial Dashboard/Ledgers dropdown -->
        @php
          $financeActive = Route::is('finance.revenue') || Route::is('finance.payments') || Route::is('finance.security-deposits');
        @endphp
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_payments'))
        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $financeActive ? '' : 'collapsed' }}" href="#financeLedgersCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $financeActive ? 'true' : 'false' }}" aria-controls="financeLedgersCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-coins text-success"></span></span>
              <span class="nav-link-text ps-1">Financial Ledgers</span>
            </div>
          </a>
          <ul class="nav collapse {{ $financeActive ? 'show' : '' }}" id="financeLedgersCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link {{ Route::is('finance.revenue') ? 'active' : '' }}" href="{{ route('finance.revenue') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Revenue Dashboard</span>
                </div>
              </a>
            </li>
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
        @endif

        <!-- General Ledger Accounting dropdown -->
        @php
          $accountingActive = Route::is('finance.financial-years') || Route::is('finance.chart-of-accounts') || Route::is('finance.opening-balances') || Route::is('finance.journal-vouchers.*') || Route::is('finance.general-ledger') || Route::is('finance.trial-balance') || Route::is('finance.profit-loss') || Route::is('finance.balance-sheet') || Route::is('finance.cash-bank');
        @endphp
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_accounting'))
        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $accountingActive ? '' : 'collapsed' }}" href="#accountingCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $accountingActive ? 'true' : 'false' }}" aria-controls="accountingCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-calculator text-primary"></span></span>
              <span class="nav-link-text ps-1">General Ledger</span>
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
              <a class="nav-link {{ Route::is('finance.profit-loss') ? 'active' : '' }}" href="{{ route('finance.profit-loss') }}">
                <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Profit & Loss</span></div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('finance.balance-sheet') ? 'active' : '' }}" href="{{ route('finance.balance-sheet') }}">
                <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Balance Sheet</span></div>
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

        <!-- Expense Management dropdown -->
        @php
          $expensesActive = Route::is('expenses.*');
        @endphp
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_expenses'))
        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $expensesActive ? '' : 'collapsed' }}" href="#expensesCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $expensesActive ? 'true' : 'false' }}" aria-controls="expensesCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-file-invoice-dollar text-danger"></span></span>
              <span class="nav-link-text ps-1">Expenses</span>
            </div>
          </a>
          <ul class="nav collapse {{ $expensesActive ? 'show' : '' }}" id="expensesCollapse" data-bs-parent="#navbarVerticalNav">
            <li class="nav-item">
              <a class="nav-link {{ Route::is('expenses.dashboard') ? 'active' : '' }}" href="{{ route('expenses.dashboard') }}">
                <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Dashboard</span></div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('expenses.index') || Route::is('expenses.show') || Route::is('expenses.create') || Route::is('expenses.edit') ? 'active' : '' }}" href="{{ route('expenses.index') }}">
                <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Expense Register</span></div>
              </a>
            </li>
            @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_expense_settings'))
            <li class="nav-item">
              <a class="nav-link {{ Route::is('expenses.categories') ? 'active' : '' }}" href="{{ route('expenses.categories') }}">
                <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Categories</span></div>
              </a>
            </li>
            @endif
            <li class="nav-item">
              <a class="nav-link {{ Route::is('expenses.petty-cash') ? 'active' : '' }}" href="{{ route('expenses.petty-cash') }}">
                <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Petty Cash</span></div>
              </a>
            </li>
            @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_expense_settings'))
            <li class="nav-item">
              <a class="nav-link {{ Route::is('expenses.budgets') ? 'active' : '' }}" href="{{ route('expenses.budgets') }}">
                <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Budget Tracker</span></div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('expenses.recurring') ? 'active' : '' }}" href="{{ route('expenses.recurring') }}">
                <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Recurring Expenses</span></div>
              </a>
            </li>
            @endif
            <li class="nav-item">
              <a class="nav-link {{ Route::is('expenses.reports') ? 'active' : '' }}" href="{{ route('expenses.reports') }}">
                <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Expense Reports</span></div>
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
        <!-- CONFIGURATION & SETTINGS SECTION -->
        <!-- ========================================== -->
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'))
        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
          <div class="col-auto navbar-vertical-label">Configuration</div>
          <div class="col ps-0"><hr class="mb-0 text-300" /></div>
        </div>

        <!-- Billing & Subscription (Owner/Tenant Settings Access) -->
        @if(auth()->user()->hasRole('owner') || auth()->user()->isSuperAdmin())
        <li class="nav-item">
          <a class="nav-link {{ Route::is('billing.*') ? 'active' : '' }}" href="{{ route('billing.index') }}" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-file-invoice-dollar text-primary"></span></span>
              <span class="nav-link-text ps-1">Billing & Subscription</span>
            </div>
          </a>
        </li>
        @endif

        <!-- Setup Wizard -->
        <li class="nav-item">
          <a class="nav-link {{ Route::is('setup.wizard') ? 'active' : '' }}" href="{{ route('setup.wizard') }}" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-magic text-info"></span></span>
              <span class="nav-link-text ps-1">Setup Wizard</span>
            </div>
          </a>
        </li>

        <!-- Branches Configuration -->
        @php
          $branchManagementActive = Route::is('branches.*');
        @endphp
        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $branchManagementActive ? '' : 'collapsed' }}" href="#branchManagementCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $branchManagementActive ? 'true' : 'false' }}" aria-controls="branchManagementCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-map-marker-alt"></span></span>
              <span class="nav-link-text ps-1">Branch Settings</span>
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

        <!-- Halls & Venues -->
        <li class="nav-item">
          <a class="nav-link {{ Route::is('halls.*') ? 'active' : '' }}" href="{{ route('halls.index') }}" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-hotel text-warning"></span></span>
              <span class="nav-link-text ps-1">Halls & Venues</span>
            </div>
          </a>
        </li>

        <!-- Booking Settings dropdown -->
        @php
          $bookingConfigActive = Route::is('slots.*') || Route::is('hall-slots.*') || Route::is('extra-services.*') || Route::is('event-types.*');
        @endphp
        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $bookingConfigActive ? '' : 'collapsed' }}" href="#bookingConfigCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $bookingConfigActive ? 'true' : 'false' }}" aria-controls="bookingConfigCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-cog"></span></span>
              <span class="nav-link-text ps-1">Booking Settings</span>
            </div>
          </a>
          <ul class="nav collapse {{ $bookingConfigActive ? 'show' : '' }}" id="bookingConfigCollapse" data-bs-parent="#navbarVerticalNav">
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
              <a class="nav-link {{ Route::is('event-types.index') ? 'active' : '' }}" href="{{ route('event-types.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Event Types</span>
                </div>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ Route::is('extra-services.index') ? 'active' : '' }}" href="{{ route('extra-services.index') }}">
                <div class="d-flex align-items-center">
                  <span class="nav-link-text ps-1">Add-ons (Services)</span>
                </div>
              </a>
            </li>
          </ul>
        </li>

        <!-- Default Settings & Numbers -->
        <li class="nav-item">
          <a class="nav-link {{ Route::is('owner.default-data') ? 'active' : '' }}" href="{{ route('owner.default-data') }}" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-list-ol text-success"></span></span>
              <span class="nav-link-text ps-1">Default Data & Numbers</span>
            </div>
          </a>
        </li>

        <!-- Inventory Accounting Settings -->
        <li class="nav-item">
          <a class="nav-link {{ Route::is('inventory.settings') ? 'active' : '' }}" href="{{ route('inventory.settings') }}" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-sliders-h text-primary"></span></span>
              <span class="nav-link-text ps-1">Inventory Settings</span>
            </div>
          </a>
        </li>
        @endif

        <!-- ========================================== -->
        <!-- ADMINISTRATION SECTION -->
        <!-- ========================================== -->
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_staff'))
        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
          <div class="col-auto navbar-vertical-label">Administration</div>
          <div class="col ps-0"><hr class="mb-0 text-300" /></div>
        </div>

        <!-- Employee Roster -->
        @php
          $staffActive = Route::is('staff.index') || Route::is('staff.show') || Route::is('staff.edit') || Route::is('staff.create');
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
                  <span class="nav-link-text ps-1">Payroll Overview</span>
                  <span class="badge badge-subtle-warning rounded-pill ms-2" style="font-size: 8px; padding: 1px 4px;">Soon</span>
                </div>
              </a>
            </li>
          </ul>
        </li>

        <!-- User Accounts & Roles -->
        @php
          $usersActive = Route::is('users.*');
        @endphp
        <li class="nav-item">
          <a class="nav-link dropdown-indicator {{ $usersActive ? '' : 'collapsed' }}" href="#usersCollapse" role="button" data-bs-toggle="collapse" aria-expanded="{{ $usersActive ? 'true' : 'false' }}" aria-controls="usersCollapse">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-users-cog"></span></span>
              <span class="nav-link-text ps-1">Users & Access</span>
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

        <!-- Roles & Permissions placeholders -->
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
