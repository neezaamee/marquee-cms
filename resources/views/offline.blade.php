<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Offline Mode — Marquee CMS</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #0b1727 0%, #192e4d 100%);
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .offline-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 16px;
            max-width: 580px;
            width: 100%;
            padding: 35px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            text-align: center;
        }
        .pulse-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: rgba(245, 128, 62, 0.15);
            border: 2px solid #f5803e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: #f5803e;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(245, 128, 62, 0.5); }
            70% { box-shadow: 0 0 0 16px rgba(245, 128, 62, 0); }
            100% { box-shadow: 0 0 0 0 rgba(245, 128, 62, 0); }
        }
        .urdu-text {
            font-family: 'Noto Nastaliq Urdu', serif;
            line-height: 1.8;
            direction: rtl;
        }
        .badge-status {
            display: inline-block;
            background: #dc3545;
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 15px;
        }
        .btn-retry {
            background: #2c7be5;
            color: #fff;
            border: none;
            padding: 10px 24px;
            font-weight: 700;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .btn-retry:hover {
            background: #1a68d1;
            color: #fff;
            transform: translateY(-1px);
        }
        .btn-cached {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 10px 20px;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
        }
        .btn-cached:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
        }
    </style>
</head>
<body>

    <div class="offline-card">
        <div class="pulse-icon">
            <i class="fas fa-wifi-slash"></i>
        </div>

        <span class="badge-status">
            <i class="fas fa-satellite-dish me-1"></i> Offline Mode / آف لائن موڈ
        </span>

        <h3 class="fw-bold mb-2">No Internet Connection</h3>
        <div class="urdu-text text-warning fs-5 mb-3">انٹرنیٹ کنکشن منقطع ہو گیا ہے</div>

        <p class="text-white-50 mb-3 fs-14">
            The broadband/cloud connection is currently unavailable. Any previously loaded 
            <strong>Kitchen Slips</strong>, <strong>Receipts</strong>, and <strong>Offline Vault Data</strong> 
            remain accessible and printable without interruption.
        </p>
        <p class="urdu-text text-white-50 fs-13 mb-4">
            کلاؤڈ کنکشن دستیاب نہیں ہے۔ تاہم پہلے سے دیکھی گئی کچن سلپس اور رسیدیں آف لائن موڈ میں پرنٹ کی جا سکتی ہیں۔
        </p>

        <div class="d-flex flex-wrap gap-2 justify-content-center">
            <button onclick="checkConnectionAndReload()" class="btn btn-retry shadow-sm">
                <i class="fas fa-redo-alt me-1"></i> Retry Connection / دوبارہ چیک کریں
            </button>
            <a href="javascript:history.back()" class="btn btn-cached">
                <i class="fas fa-arrow-left me-1"></i> Back to Last Screen
            </a>
        </div>

        <div id="reconnect-alert" class="alert alert-success mt-4 d-none py-2 px-3 fs-13" role="alert">
            <i class="fas fa-check-circle me-1"></i> Connection restored! Reloading Marquee CMS...
        </div>
    </div>

    <script>
        function checkConnectionAndReload() {
            if (navigator.onLine) {
                window.location.reload();
            } else {
                fetch('/manifest.json', { cache: 'no-store' })
                    .then(() => window.location.reload())
                    .catch(() => {
                        const btn = document.querySelector('.btn-retry');
                        btn.innerHTML = '<i class="fas fa-times me-1"></i> Still Offline. Checking...';
                        setTimeout(() => {
                            btn.innerHTML = '<i class="fas fa-redo-alt me-1"></i> Retry Connection / دوبارہ چیک کریں';
                        }, 2000);
                    });
            }
        }

        window.addEventListener('online', function() {
            const alertEl = document.getElementById('reconnect-alert');
            if (alertEl) alertEl.classList.remove('d-none');
            setTimeout(() => window.location.reload(), 1500);
        });
    </script>
</body>
</html>
