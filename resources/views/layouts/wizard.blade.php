<!DOCTYPE html>
<html data-bs-theme="light" lang="en-US" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Initial Setup Wizard - Marquee CMS</title>

    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/favicons/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/favicons/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/img/favicons/favicon-16x16.png') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/img/favicons/favicon.ico') }}">
    <link rel="manifest" href="{{ asset('assets/img/favicons/manifest.json') }}">
    <meta name="theme-color" content="#ffffff">
    <script src="{{ asset('assets/js/config.js') }}"></script>
    <script src="{{ asset('vendors/simplebar/simplebar.min.js') }}"></script>

    <!-- Stylesheets -->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:300,400,500,600,700,800,900&amp;display=swap" rel="stylesheet">
    <link href="{{ asset('vendors/simplebar/simplebar.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/theme-rtl.css') }}" rel="stylesheet" id="style-rtl">
    <link href="{{ asset('assets/css/theme.css') }}" rel="stylesheet" id="style-default">
    <link href="{{ asset('assets/css/user-rtl.css') }}" rel="stylesheet" id="user-style-rtl">
    <link href="{{ asset('assets/css/user.css') }}" rel="stylesheet" id="user-style-default">

    @livewireStyles
    
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

    <style>
      body {
        min-height: 100vh;
        background-color: var(--falcon-body-bg);
      }
    </style>
  </head>

  <body class="bg-body-tertiary">
    <main class="main" id="top">
      <div class="container py-5">
        <div class="row justify-content-center">
          <div class="col-lg-10 col-xl-9">
            <div class="d-flex align-items-center justify-content-center mb-4">
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
                <img class="me-2" src="{{ $logoUrl }}" alt="Logo" width="45" style="object-fit: contain; max-height: 45px;" />
              @else
                <img class="me-2" src="{{ asset('assets/img/icons/spot-illustrations/falcon.png') }}" alt="Logo" width="45" />
              @endif
              <span class="font-sans-serif text-dark fw-bolder fs-3">marquee<span class="text-primary fw-semibold">cms</span></span>
            </div>
            
            {{ $slot }}
          </div>
        </div>
      </div>
    </main>

    <!-- JavaScripts -->
    <script src="{{ asset('vendors/popper/popper.min.js') }}"></script>
    <script src="{{ asset('vendors/bootstrap/bootstrap.min.js') }}"></script>
    <script src="{{ asset('vendors/fontawesome/all.min.js') }}"></script>
    <script src="{{ asset('vendors/lodash/lodash.min.js') }}"></script>
    <script src="{{ asset('assets/js/theme.js') }}"></script>
    @livewireScripts
  </body>
</html>
