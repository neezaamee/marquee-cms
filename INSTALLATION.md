# Local Development Installation Guide

Follow this guide to install and run MarqueeCMS on your local machine.

## Prerequisites
* **PHP**: 8.2 or 8.3
* **Composer**: 2.x
* **Node.js**: 18.x or 20.x (with NPM)
* **Database**: MySQL 8.x or MariaDB 10.x
* **Web Server**: Apache/Nginx (e.g. WampServer, XAMPP, Laragon) or PHP Built-in server

---

## Step-by-Step Setup

### 1. Clone the Codebase
Clone the project repository to your web server root directory:
```bash
git clone https://github.com/neezaamee/marquee-cms.git
cd MarqueeCMS
```

### 2. Dependency Installations
Run Composer to install all backend vendor packages:
```bash
composer install
```

Install frontend Node dependencies:
```bash
npm install
```

### 3. Environment Setup
Duplicate the sample environment file:
```bash
cp .env.example .env
```

Generate the application encryption key:
```bash
php artisan key:generate
```

### 4. Database Creation & Migration
Create a blank database named `marquee_cms` in your local MySQL instance.

Edit the `.env` database configuration details:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=marquee_cms
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Run database tables migrations and seed default platform settings:
```bash
php artisan migrate --seed
```

### 5. Storage Symlink
Generate the public storage symlink to enable media and file uploads rendering:
```bash
php artisan storage:link
```

### 6. Starting Dev Server
Run Vite hot reloading for asset compilations:
```bash
npm run dev
```

In a separate terminal tab, start the local PHP development server:
```bash
php artisan serve
```

Go to `http://127.0.0.1:8000` in your web browser to access the platform.
