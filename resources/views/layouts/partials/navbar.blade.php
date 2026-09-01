<nav class="navbar navbar-light navbar-glass navbar-top navbar-expand">

  <button class="btn navbar-toggler-humburger-icon navbar-toggler me-1 me-sm-3" type="button" data-bs-toggle="collapse" data-bs-target="#navbarVerticalCollapse" aria-controls="navbarVerticalCollapse" aria-expanded="false" aria-label="Toggle Navigation">
    <span class="navbar-toggle-icon"><span class="toggle-line"></span></span>
  </button>
  
  <a class="navbar-brand me-1 me-sm-3" href="{{ route('dashboard') }}">
    <div class="d-flex align-items-center">
      @php
        $activeMarquee = null;
        if (auth()->check()) {
            $activeMarqueeId = auth()->user()->getActiveMarqueeId();
            if ($activeMarqueeId) {
                $activeMarquee = \App\Models\Marquee::find($activeMarqueeId);
            }
        }
      @endphp
      @if($activeMarquee && $activeMarquee->logo)
        @php
          $logoUrl = Str::startsWith($activeMarquee->logo, ['http://', 'https://']) 
            ? $activeMarquee->logo 
            : (Str::startsWith($activeMarquee->logo, 'storage/') ? asset($activeMarquee->logo) : asset('storage/' . $activeMarquee->logo));
        @endphp
        <img class="me-2" src="{{ $logoUrl }}" alt="Logo" width="50" style="object-fit: contain; max-height: 50px;" />
      @else
        <img class="me-2" src="{{ asset('assets/img/icons/spot-illustrations/falcon.png') }}" alt="Logo" width="50" />
      @endif
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

  <!-- Right: Utilities, Business Switcher and User Info -->
  <ul class="navbar-nav navbar-nav-icons ms-auto flex-row align-items-center">
    
    <!-- Active Business Switcher Dropdown -->
    @php
      $navUser = auth()->user();
      $navAccessibleMarquees = $navUser ? $navUser->getAccessibleMarquees()->where('status', 'active') : collect();
      $navActiveMarqueeId = $navUser ? $navUser->getActiveMarqueeId() : null;
      $navActiveMarquee = $navAccessibleMarquees->firstWhere('id', $navActiveMarqueeId) ?? ($navActiveMarqueeId ? \App\Models\Marquee::find($navActiveMarqueeId) : null);
    @endphp

    @if($navUser && !$navUser->isSuperAdmin() && ($navAccessibleMarquees->count() > 1 || $navUser->isBusinessOwner() || $navUser->isAreaManager()))
    <li class="nav-item dropdown me-2">
      <a class="btn btn-sm btn-outline-primary dropdown-toggle d-flex align-items-center gap-1 py-1 px-2 rounded-pill shadow-none" href="#" role="button" id="activeBusinessDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <span class="fas fa-building fs-10 text-primary"></span>
        <span class="fs-11 fw-semibold text-truncate d-inline-block" style="max-width: 140px;">
          {{ $navActiveMarquee ? $navActiveMarquee->name : 'Select Business' }}
        </span>
      </a>
      <div class="dropdown-menu dropdown-menu-end dropdown-caret border py-1 mt-2 shadow-sm" aria-labelledby="activeBusinessDropdown" style="min-width: 230px;">
        <h6 class="dropdown-header text-uppercase fs-11 text-500 py-1">Active Business</h6>
        <div class="dropdown-divider my-1"></div>
        @forelse($navAccessibleMarquees as $m)
          <form action="{{ route('marquee.switch') }}" method="POST" class="m-0 p-0">
            @csrf
            <input type="hidden" name="marquee_id" value="{{ $m->id }}">
            <button type="submit" class="dropdown-item d-flex align-items-center justify-content-between py-2 {{ (int)$m->id === (int)$navActiveMarqueeId ? 'active bg-subtle-primary text-primary fw-bold' : '' }}">
              <span class="d-flex align-items-center gap-2 text-truncate">
                <span class="fas fa-check text-primary {{ (int)$m->id === (int)$navActiveMarqueeId ? '' : 'invisible' }}" style="font-size: 10px;"></span>
                <span class="text-truncate" style="max-width: 130px;">{{ $m->name }}</span>
              </span>
              @if($m->city)
                <span class="badge bg-200 text-600 rounded-pill fs-11 ms-1">{{ $m->city }}</span>
              @endif
            </button>
          </form>
        @empty
          <div class="dropdown-item text-muted fs-11 py-2">No businesses available</div>
        @endforelse

        @if($navUser->isBusinessOwner())
          <div class="dropdown-divider my-1"></div>
          <a class="dropdown-item d-flex align-items-center gap-2 text-primary fs-11 py-2" href="{{ route('marquees.create') }}">
            <span class="fas fa-plus-circle"></span> Add New Business
          </a>
        @endif
      </div>
    </li>
    @elseif($navActiveMarquee && !$navUser->isSuperAdmin())
    <li class="nav-item me-2 d-none d-sm-block">
      <span class="badge badge-subtle-primary py-2 px-3 rounded-pill fs-11 d-flex align-items-center gap-1">
        <span class="fas fa-building"></span> {{ $navActiveMarquee->name }}
        @if($navUser && $navUser->branch)
          <span class="text-400">|</span> <span class="fas fa-code-branch"></span> {{ $navUser->branch->name }}
        @endif
      </span>
    </li>
    @endif

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
          @if(auth()->user()->profile_photo)
            <img class="rounded-circle" src="{{ asset('storage/' . auth()->user()->profile_photo) }}" alt="User Profile" style="object-fit: cover;" />
          @elseif(auth()->user()->employee && auth()->user()->employee->photo)
            <img class="rounded-circle" src="{{ asset('storage/' . auth()->user()->employee->photo) }}" alt="User Profile" style="object-fit: cover;" />
          @else
            <div class="avatar-name rounded-circle bg-subtle-primary text-primary d-flex align-items-center justify-content-center fw-bold" style="width: 100%; height: 100%; font-size: 0.85rem;">
              <span>{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
            </div>
          @endif
        </div>
      </a>
      <div class="dropdown-menu dropdown-caret dropdown-menu-end py-0" aria-labelledby="navbarDropdownUser">
        <div class="bg-white dark__bg-1000 rounded-2 py-2">
          <div class="px-3 py-2">
            <h6 class="mb-0 fw-bold">{{ auth()->user()->name ?? 'Administrator' }}</h6>
            <p class="fs-11 text-600 mb-0">{{ auth()->user()->email ?? 'admin@marquee.cms' }}</p>
          </div>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item" href="{{ route('profile.show') }}">Profile &amp; Account</a>
          <a class="dropdown-item" href="{{ route('profile.show') . '#tab-personal' }}">Settings</a>
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
