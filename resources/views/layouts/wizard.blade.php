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
    <link href="{{ asset('assets/css/theme.css') }}" rel="stylesheet" id="style-default">
    <link href="{{ asset('assets/css/user.css') }}" rel="stylesheet" id="user-style-default">

    @livewireStyles
    
    <style>
      body {
        background: linear-gradient(135deg, #0b0f19 0%, #111827 50%, #1f2937 100%);
        color: #f9fafb;
        min-height: 100vh;
        font-family: 'Poppins', sans-serif;
      }
      .wizard-card {
        background: rgba(17, 24, 39, 0.75);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
      }
      .wizard-header {
        background: linear-gradient(90deg, #2563eb 0%, #7c3aed 100%);
        border-radius: 20px 20px 0 0;
        padding: 30px;
      }
      .form-control, .form-select {
        background: rgba(10, 15, 26, 0.6) !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
        color: #f3f4f6 !important;
        border-radius: 8px;
        padding: 10px 14px;
      }
      .form-control:focus, .form-select:focus {
        background: rgba(10, 15, 26, 0.9) !important;
        border-color: #3b82f6 !important;
        color: #ffffff !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25) !important;
      }
      .form-control::placeholder {
        color: #6b7280;
      }
      .form-label {
        color: #d1d5db;
        font-weight: 500;
        font-size: 14px;
      }
      .step-indicator {
        display: flex;
        justify-content: space-between;
        margin-bottom: 40px;
        position: relative;
      }
      .step-indicator::before {
        content: '';
        position: absolute;
        top: 18px;
        left: 0;
        right: 0;
        height: 3px;
        background: rgba(255, 255, 255, 0.08);
        z-index: 1;
      }
      .step-item {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
      }
      .step-number {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #1f2937;
        border: 2px solid #374151;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: #9ca3af;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
      }
      .step-item.active .step-number {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
        box-shadow: 0 0 15px rgba(37, 99, 235, 0.6);
        transform: scale(1.15);
      }
      .step-item.completed .step-number {
        background: #10b981;
        border-color: #10b981;
        color: #ffffff;
      }
      .step-label {
        font-size: 12px;
        margin-top: 10px;
        color: #9ca3af;
        white-space: nowrap;
        font-weight: 500;
        transition: all 0.3s ease;
      }
      .step-item.active .step-label {
        color: #3b82f6;
        font-weight: 600;
      }
      .step-item.completed .step-label {
        color: #10b981;
      }
      .text-light-muted {
        color: #9ca3af;
      }
      .alert-subtle-warning {
        background-color: rgba(217, 119, 6, 0.15);
        border: 1px solid rgba(217, 119, 6, 0.3);
        color: #f59e0b;
      }
    </style>
  </head>

  <body>
    <main class="main" id="top">
      <div class="container py-5">
        <div class="row justify-content-center">
          <div class="col-lg-10 col-xl-9">
            <div class="d-flex align-items-center justify-content-center mb-4">
              <img class="me-2" src="{{ asset('assets/img/icons/spot-illustrations/falcon.png') }}" alt="Logo" width="45" />
              <span class="font-sans-serif text-white fw-bolder fs-3">marquee<span class="text-primary fw-semibold">cms</span></span>
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
    @livewireScripts
  </body>
</html>
