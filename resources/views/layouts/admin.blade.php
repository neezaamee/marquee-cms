<!DOCTYPE html>
<html data-bs-theme="light" lang="en-US" dir="ltr">

  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- ===============================================-->
    <!--    Document Title-->
    <!-- ===============================================-->
    <title>@yield('title', 'Marquee CMS') - Falcon</title>

    <!-- ===============================================-->
    <!--    Favicons-->
    <!-- ===============================================-->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/favicons/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/favicons/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/img/favicons/favicon-16x16.png') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/img/favicons/favicon.ico') }}">
    <link rel="manifest" href="{{ asset('assets/img/favicons/manifest.json') }}">
    <meta name="msapplication-TileImage" content="{{ asset('assets/img/favicons/mstile-150x150.png') }}">
    <meta name="theme-color" content="#ffffff">
    <script src="{{ asset('assets/js/config.js') }}"></script>
    <script src="{{ asset('vendors/simplebar/simplebar.min.js') }}"></script>

    <!-- ===============================================-->
    <!--    Stylesheets-->
    <!-- ===============================================-->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:300,400,500,600,700,800,900&amp;display=swap" rel="stylesheet">
    <link href="{{ asset('vendors/simplebar/simplebar.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/flatpickr/flatpickr.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/theme-rtl.css') }}" rel="stylesheet" id="style-rtl">
    <link href="{{ asset('assets/css/theme.css') }}" rel="stylesheet" id="style-default">
    <link href="{{ asset('assets/css/user-rtl.css') }}" rel="stylesheet" id="user-style-rtl">
    <link href="{{ asset('assets/css/user.css') }}" rel="stylesheet" id="user-style-default">
    
    <style>
      /* Global Application Top Progress Bar */
      .global-progress-bar {
        position: fixed;
        top: 0;
        left: 0;
        width: 0%;
        height: 3px;
        z-index: 999999;
        pointer-events: none;
        opacity: 0;
        background: linear-gradient(90deg, #2c7be5 0%, #00d27a 50%, #2c7be5 100%);
        background-size: 200% 100%;
        box-shadow: 0 0 10px rgba(44, 123, 229, 0.8), 0 0 5px rgba(0, 210, 122, 0.8);
        transition: width 0.2s cubic-bezier(0.1, 0.85, 0.25, 1), opacity 0.25s ease;
      }

      /* Global Livewire Floating Status Badge */
      .global-livewire-badge {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 999998;
        pointer-events: none;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border: 1px solid rgba(44, 123, 229, 0.25);
        border-radius: 50rem;
        box-shadow: 0 4px 20px rgba(44, 123, 229, 0.15), 0 2px 6px rgba(0, 0, 0, 0.06);
        padding: 7px 16px;
        font-size: 12px;
        font-weight: 600;
        color: #2c7be5;
        display: flex;
        align-items: center;
        gap: 8px;
        opacity: 0;
        transform: translateY(12px) scale(0.95);
        transition: opacity 0.22s cubic-bezier(0.4, 0, 0.2, 1), transform 0.22s cubic-bezier(0.4, 0, 0.2, 1);
      }
      [data-bs-theme="dark"] .global-livewire-badge {
        background: rgba(18, 26, 44, 0.92);
        border-color: rgba(44, 123, 229, 0.4);
        color: #6199e8;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
      }
      .global-livewire-badge.show {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    </style>

    @yield('styles')

    <script>
      var isRTL = JSON.parse(localStorage.getItem('isRTL'));
      if (isRTL) {
        var linkDefault = document.getElementById('style-default');
        var userLinkDefault = document.getElementById('user-style-default');
        linkDefault.setAttribute('disabled', true);
        userLinkDefault.setAttribute('disabled', true);
        document.querySelector('html').setAttribute('dir', 'rtl');
      } else {
        var linkRTL = document.getElementById('style-rtl');
        var userLinkRTL = document.getElementById('user-style-rtl');
        linkRTL.setAttribute('disabled', true);
        userLinkRTL.setAttribute('disabled', true);
      }
    </script>
  </head>

  <body>

    <!-- Global Loading Progress Bar & Livewire Indicator -->
    <div id="global-progress-bar" class="global-progress-bar"></div>
    <div id="global-livewire-badge" class="global-livewire-badge">
      <div class="spinner-border spinner-border-sm text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <span>Processing...</span>
    </div>

    <!-- ===============================================-->
    <!--    Main Content-->
    <!-- ===============================================-->
    <main class="main" id="top">
      <div class="container" data-layout="container">
        <script>
          var isFluid = JSON.parse(localStorage.getItem('isFluid'));
          if (isFluid) {
            var container = document.querySelector('[data-layout]');
            container.classList.remove('container');
            container.classList.add('container-fluid');
          }
        </script>

        <!-- Sidebar Navigation -->
        @include('layouts.partials.sidebar')

        <div class="content">
          
          <!-- Top Navbar -->
          @include('layouts.partials.navbar')

          <!-- Main View Content -->
          @yield('content')
          {{ $slot ?? '' }}

          <!-- Footer -->
          @include('layouts.partials.footer')
          
        </div>

      </div>
    </main>
    <!-- ===============================================-->
    <!--    End of Main Content-->
    <!-- ===============================================-->

    <!-- ===============================================-->
    <!--    JavaScripts-->
    <!-- ===============================================-->
    <script src="{{ asset('vendors/popper/popper.min.js') }}"></script>
    <script src="{{ asset('vendors/bootstrap/bootstrap.min.js') }}"></script>
    <script src="{{ asset('vendors/anchorjs/anchor.min.js') }}"></script>
    <script src="{{ asset('vendors/is/is.min.js') }}"></script>
    <script src="{{ asset('vendors/fontawesome/all.min.js') }}"></script>
    <script src="{{ asset('vendors/lodash/lodash.min.js') }}"></script>
    <script src="{{ asset('vendors/list.js/list.min.js') }}"></script>
    <script src="{{ asset('vendors/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('assets/js/theme.js') }}"></script>

    <!-- Global Loader Automation (Livewire 3 + Page Navigation) -->
    <script>
      (function() {
        const progressBar = document.getElementById('global-progress-bar');
        const badge = document.getElementById('global-livewire-badge');
        let activeRequests = 0;
        let progressInterval = null;

        window.startGlobalLoader = function() {
          activeRequests++;
          if (activeRequests === 1) {
            if (badge) badge.classList.add('show');
            if (progressBar) {
              progressBar.style.opacity = '1';
              progressBar.style.width = '25%';
              clearInterval(progressInterval);
              let w = 25;
              progressInterval = setInterval(function() {
                if (w < 85) {
                  w += (85 - w) * 0.12;
                  progressBar.style.width = w + '%';
                }
              }, 120);
            }
          }
        };

        window.stopGlobalLoader = function() {
          activeRequests = Math.max(0, activeRequests - 1);
          if (activeRequests === 0) {
            clearInterval(progressInterval);
            if (progressBar) {
              progressBar.style.width = '100%';
              setTimeout(function() {
                progressBar.style.opacity = '0';
                setTimeout(function() {
                  progressBar.style.width = '0%';
                }, 250);
              }, 180);
            }
            if (badge) {
              setTimeout(function() {
                if (activeRequests === 0) badge.classList.remove('show');
              }, 180);
            }
          }
        };

        // Livewire 3 Hook Integration
        function setupLivewireLoader() {
          if (window.Livewire) {
            Livewire.hook('commit', ({ commit, respond, succeed, fail }) => {
              window.startGlobalLoader();
              respond(() => window.stopGlobalLoader());
              succeed(() => window.stopGlobalLoader());
              fail(() => window.stopGlobalLoader());
            });
          }
        }

        if (window.Livewire) {
          setupLivewireLoader();
        } else {
          document.addEventListener('livewire:init', setupLivewireLoader);
        }

        // Standard Form Submissions (without Livewire)
        document.addEventListener('submit', function(e) {
          const form = e.target;
          if (form && !form.hasAttribute('wire:submit') && !form.hasAttribute('wire:submit.prevent') && !e.defaultPrevented) {
            window.startGlobalLoader();
          }
        });

        // Browser navigation & page transitions
        window.addEventListener('beforeunload', function() {
          window.startGlobalLoader();
        });
      })();
    </script>

    @yield('scripts')

  </body>

</html>
