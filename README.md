# MarqueeCMS — Enterprise Multi-Tenant Marquee & Banquet ERP SaaS

**MarqueeCMS** is a comprehensive, enterprise-grade Multi-Tenant Software-as-a-Service (SaaS) ERP designed specifically for marquee owners, wedding hall operators, banquet managers, and catering businesses. It provides an end-to-end operational workflow covering online bookings, guest confirmation tracking, operational summary dashboards, global default master data provisioning, kitchen inventory management, department stock requests/issues/productions, daily staff attendance, petty cash accounting, event checklisting, and vendor commission tracking.

---

## 🚀 Key Features

* **Multi-Tenant SaaS Architecture**: Dynamic onboarding setup wizard, isolated databases (tenant and branch level query scoping), and Stripe multi-currency subscriptions.
* **Global Default Data Management System**: Super Admin portal (`/admin/global-defaults`) managing standard templates across 9 categories (Event Types, Menu Categories, Inventory Categories, Units, Expense Categories, Department Types, Vendor Types, Customer Types, Payment Methods) with automated onboarding cloning and Marquee Owner one-click importer (`/settings/default-data`).
* **Operational Booking Dashboard**: Real-time DB summary cards (Total Bookings, Confirmed, Tentative, Today's Events, Upcoming, This Month, Pending Approvals, Payment Outstanding) with `wire:click` automatic filtering, segregated booking status vs guest confirmation headcounts, and quick inline approval workflows.
* **Department Management Module**: Complete department hierarchy (BBQ Kitchen, Housekeeping, Accounts, IT, Procurement), department employee assignments, daily attendance, stock requests, stock issues, stock returns, ledger tracking, and production batch logs.
* **Kitchen & Catering Recipes**: Linked dish menus to raw inventory items, with automated per-head material requirement calculations (`RecipeService`).
* **HR & Staff Attendance**: Employees directory, monthly payroll overview, and branch-isolated daily check-in/out attendance logging.
* **Financial Ledger & Accounting**: General ledger, chart of accounts, journal vouchers, trial balance, profit & loss, and balance sheets.
* **Expense Management**: Multi-branch petty cash drawers, recurring templates, and role-based approval rules.
* **Operational Event Checklists**: Coordination checksheets (stage setup, sound check, catering prep) assigned to staff.
* **Vendor Commissions**: Third-party event vendor profile directories and booking commission paybacks tracker.
* **Regional Integrations**: FBR (Federal Board of Revenue) sandbox POS synchronization client with QR codes verification.

---

## 🛠️ Technology Stack

* **Backend**: Laravel 12 (PHP 8.2+)
* **Frontend**: Livewire 3, Tailwind CSS, Bootstrap 5
* **Theme**: Falcon Admin Template
* **Database**: MySQL / SQLite (for unit & feature tests)
* **Integrations**: Stripe API (Subscriptions), FBR POS API

---

## 💻 Installation & Local Development

### 1. Prerequisites
Ensure you have the following installed locally:
* PHP 8.2 or higher
* Composer
* Node.js & NPM
* MySQL Server (WAMP/XAMPP, Laragon, or Docker)

### 2. Setup Steps

Clone the repository and install dependencies:
```bash
git clone https://github.com/neezaamee/marquee-cms.git
cd MarqueeCMS
composer install
npm install
```

Configure the environment file:
```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` and set your local database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=marquee_cms
DB_USERNAME=root
DB_PASSWORD=
```

Create database, run migrations, and seed global default masters, plans, and roles:
```bash
php artisan migrate
php artisan db:seed
```

Generate the public storage symlink:
```bash
php artisan storage:link
```

Compile assets and start the local development server:
```bash
npm run dev
php artisan serve
```

Go to `http://127.0.0.1:8000` in your web browser to access the platform.

---

## 🔐 Default Login Credentials

For testing and local development, the main database seeder initializes the platform credentials:

* **Super Admin Login (Central Platform)**:
  * **Email**: `superadmin@elaftech.com`
  * **Password**: `Password123!`

* **Marquee Tenant Owner Login (The Sheraton Marquee)**:
  * **Email**: `ghulamabbas@thesheraton.com`
  * **Password**: `Password123!`

---

## ⚙️ Queue & Scheduler Configuration

### 1. Queues
MarqueeCMS uses background job queues to dispatch automated FBR POS sync requests, email notifications, and invoice generation.
* Local development: `QUEUE_CONNECTION=database` or `sync`
* Production: `QUEUE_CONNECTION=redis` or `database`

Start the queue worker:
```bash
php artisan queue:work
```

### 2. Task Scheduler
For recurring expenses and trial expiration checks, configure a CRON job on your hosting server to run the Laravel scheduler every minute:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📂 Project Directory Structure

```text
├── app/
│   ├── Http/
│   │   ├── Controllers/    # CRM, Billing, Expense & Department controllers
│   │   └── Middleware/     # Wizard checks & route security
│   ├── Livewire/           # Interactive components (SuperAdmin, Owner, Booking, Department)
│   ├── Models/             # Eloquent schema models (Booking, GlobalDefaultMaster, Department, etc.)
│   ├── Services/           # RecipeService, DepartmentStockService, FbrPosService
│   └── Traits/             # BelongsToTenant & BelongsToBranch isolation scopes
├── config/                 # Services and application configs
├── database/
│   ├── migrations/         # ERP database table schemas
│   └── seeders/            # Global defaults, plans, and role seeders
├── docs/                   # Developer & architecture documentation
├── resources/
│   └── views/              # Blade layouts and Falcon Admin subviews templates
├── routes/
│   └── web.php             # Web & Livewire routes
└── tests/
    └── Feature/            # Automated test suite (Department, GlobalDefaults, Booking, etc.)
```

---

## 📄 License
This project is licensed under the terms of the [MIT License](LICENSE).
