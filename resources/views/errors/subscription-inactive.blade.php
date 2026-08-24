<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Subscription Inactive - Marquee CMS</title>
    <!-- Stylesheets -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:100,200,300,400,500,600,700,800,900&amp;display=swap" rel="stylesheet">
    <link href="{{ asset('assets/css/theme.min.css') }}" rel="stylesheet" id="style-default">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row min-vh-100 flex-center p-5">
            <div class="col-12 col-md-8 col-lg-6 col-xxl-4">
                <div class="card border-0 shadow-lg text-center" style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 15px;">
                    <div class="card-body p-5">
                        <div class="mb-4">
                            <span class="fas fa-exclamation-triangle fa-5x text-warning animate-bounce"></span>
                        </div>
                        
                        <h3 class="fw-extrabold text-dark mb-3">Subscription Inactive</h3>
                        
                        @if($reason === 'suspended')
                            <p class="text-secondary fs-13 mb-4">
                                Your business account has been <strong>Suspended</strong> by the system administrator. Access to the Marquee application is temporarily blocked.
                            </p>
                        @elseif($reason === 'inactive')
                            <p class="text-secondary fs-13 mb-4">
                                Your business account status is set to <strong>Inactive</strong>. Access to all operational modules is disabled.
                            </p>
                        @else
                            <p class="text-secondary fs-13 mb-4">
                                Your SaaS subscription plan trial or billing cycle has <strong>Expired</strong>.
                            </p>
                        @endif

                        <div class="border rounded p-3 bg-light mb-4">
                            @if(auth()->user()->hasRole('owner') || auth()->user()->isBusinessOwner())
                                <div class="text-start fs-12 text-secondary">
                                    <span class="fas fa-info-circle text-primary me-2"></span>
                                    As the Business Owner, you can reactivate your account by making a payment or changing your subscription plan.
                                </div>
                                <div class="mt-3">
                                    <a class="btn btn-primary w-100 btn-sm shadow-none" href="{{ route('billing.index') }}" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%) !important;">
                                        <span class="fas fa-credit-card me-2"></span>Go to Billing Dashboard
                                    </a>
                                </div>
                            @else
                                <div class="text-start fs-12 text-secondary">
                                    <span class="fas fa-info-circle text-primary me-2"></span>
                                    Please contact your business account administrator or manager to renew the subscription plan dues and reactivate access.
                                </div>
                            @endif
                        </div>

                        <div class="d-flex justify-content-center gap-3">
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-falcon-default btn-sm px-4">
                                    <span class="fas fa-sign-out-alt me-2"></span>Log Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
