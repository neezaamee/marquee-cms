<nav class="navbar navbar-light navbar-glass navbar-top navbar-expand">

  <button class="btn navbar-toggler-humburger-icon navbar-toggler me-1 me-sm-3" type="button" data-bs-toggle="collapse" data-bs-target="#navbarVerticalCollapse" aria-controls="navbarVerticalCollapse" aria-expanded="false" aria-label="Toggle Navigation">
    <span class="navbar-toggle-icon"><span class="toggle-line"></span></span>
  </button>
  
  <a class="navbar-brand me-1 me-sm-3" href="{{ route('dashboard') }}">
    <div class="d-flex align-items-center">
      <img class="me-2" src="{{ asset('assets/img/icons/spot-illustrations/falcon.png') }}" alt="Logo" width="50" />
      <span class="font-sans-serif text-primary">marquee</span>
    </div>
  </a>

  <!-- Left: Quick Search -->
  <ul class="navbar-nav align-items-center d-none d-lg-block">
    <li class="nav-item">
      <div class="search-box">
        <form class="position-relative">
          <input class="form-control search-input" type="search" placeholder="Search booking/customer..." aria-label="Search" />
          <span class="fas fa-search search-box-icon"></span>
        </form>
      </div>
    </li>
  </ul>

  <!-- Right: Utilities and User Info -->
  <ul class="navbar-nav navbar-nav-icons ms-auto flex-row align-items-center">
    
    <!-- Light/Dark Mode Switcher -->
    <li class="nav-item ps-2 pe-0">
      <div class="dropdown theme-control-dropdown">
        <a class="nav-link d-flex align-items-center dropdown-toggle fa-icon-wait fs-9 pe-1 py-0" href="#" role="button" id="themeSwitchDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <span class="fas fa-sun fs-7" data-theme-dropdown-toggle-icon="light"></span>
          <span class="fas fa-moon fs-7" data-theme-dropdown-toggle-icon="dark"></span>
          <span class="fas fa-adjust fs-7" data-theme-dropdown-toggle-icon="auto"></span>
        </a>
        <div class="dropdown-menu dropdown-menu-end dropdown-caret border py-0 mt-3" aria-labelledby="themeSwitchDropdown">
          <div class="bg-white dark__bg-1000 rounded-2 py-2">
            <button class="dropdown-item d-flex align-items-center gap-2" type="button" value="light" data-theme-control="theme">
              <span class="fas fa-sun"></span>Light <span class="fas fa-check dropdown-check-icon ms-auto text-600"></span>
            </button>
            <button class="dropdown-item d-flex align-items-center gap-2" type="button" value="dark" data-theme-control="theme">
              <span class="fas fa-moon"></span>Dark <span class="fas fa-check dropdown-check-icon ms-auto text-600"></span>
            </button>
            <button class="dropdown-item d-flex align-items-center gap-2" type="button" value="auto" data-theme-control="theme">
              <span class="fas fa-adjust"></span>Auto <span class="fas fa-check dropdown-check-icon ms-auto text-600"></span>
            </button>
            <div class="dropdown-divider"></div>
            <div class="dropdown-item d-flex align-items-center justify-content-between gap-3 font-sans-serif" style="cursor: default;">
              <label class="form-check-label fs-10 fw-semi-bold mb-0" for="mode-fluid" style="cursor: pointer;">Fluid Layout</label>
              <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="mode-fluid" data-theme-control="isFluid" style="cursor: pointer;" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </li>

    <!-- User Profile Dropdown -->
    <li class="nav-item dropdown">
      <a class="nav-link pe-0 ps-2" id="navbarDropdownUser" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <div class="avatar avatar-xl">
          <img class="rounded-circle" src="{{ asset('assets/img/team/3-thumb.png') }}" alt="User Profile" />
        </div>
      </a>
      <div class="dropdown-menu dropdown-caret dropdown-menu-end py-0" aria-labelledby="navbarDropdownUser">
        <div class="bg-white dark__bg-1000 rounded-2 py-2">
          <div class="px-3 py-2">
            <h6 class="mb-0 fw-bold">{{ auth()->user()->name ?? 'Administrator' }}</h6>
            <p class="fs-11 text-600 mb-0">{{ auth()->user()->email ?? 'admin@marquee.cms' }}</p>
          </div>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item" href="#!">Profile &amp; Account</a>
          <a class="dropdown-item" href="#!">Settings</a>
          <div class="dropdown-divider"></div>
          
          <!-- Manual Post Request for Logout -->
          <a class="dropdown-item text-danger" href="{{ route('logout') }}"
             onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            Logout
          </a>
          <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
          </form>
        </div>
      </div>
    </li>
  </ul>
</nav>
