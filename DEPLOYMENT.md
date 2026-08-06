# Production Deployment Guide

This guide describes how to configure and deploy MarqueeCMS to a production server (Ubuntu VPS, Nginx, PHP-FPM, MySQL).

---

## 1. Server Environment Setup

Ensure your VPS has PHP 8.2/8.3, Nginx, MySQL, and Redis installed.

### Required PHP Extensions:
* `openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `curl`, `zip`, `gd`.

---

## 2. Deploying Codebase

Clone the repository directly into your production directory, or build a zip package and upload it.
```bash
cd /var/www
git clone https://github.com/neezaamee/marquee-cms.git marquee-cms
cd marquee-cms
```

Install production dependencies:
```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

Configure permissions (ensure `www-data` owns the storage and bootstrap folders):
```bash
chown -R www-data:www-data /var/www/marquee-cms/storage
chown -R www-data:www-data /var/www/marquee-cms/bootstrap/cache
chmod -R 775 /var/www/marquee-cms/storage
chmod -R 775 /var/www/marquee-cms/bootstrap/cache
```

---

## 3. Environment Configurations

Modify `.env` for production optimization:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourmarqueeerp.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=marquee_prod
DB_USERNAME=db_prod_user
DB_PASSWORD=secure_production_password

QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
CACHE_STORE=redis
```

---

## 4. Production Optimizations

After configuration, run the Laravel optimization caching commands:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Run database migrations (ensure backup is done before):
```bash
php artisan migrate --force
```

---

## 5. Process Monitoring & Queues

### Laravel Queue Worker (Supervisor)
Create a Supervisor config file at `/etc/supervisor/conf.d/laravel-worker.conf`:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/marquee-cms/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/marquee-cms/storage/logs/worker.log
stopwaitsecs=3600
```
Update and start Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### Laravel Scheduler (Cron)
Add a cron entry for the web server user:
```bash
* * * * * cd /var/www/marquee-cms && php artisan schedule:run >> /dev/null 2>&1
```

---

## 6. Nginx Web Server Configuration

Example virtual host configuration:
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourmarqueeerp.com;
    root /var/www/marquee-cms/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```
Set up SSL using Certbot / Let's Encrypt:
```bash
sudo certbot --nginx -d yourmarqueeerp.com
```
