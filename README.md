# MarqueeCMS — Enterprise Multi-Tenant Marquee & Banquet ERP SaaS

MarqueeCMS is a comprehensive, enterprise-grade Multi-Tenant Software-as-a-Service (SaaS) ERP designed specifically for marquee owners, wedding hall operators, and banquet managers. It provides a complete workflow from online bookings and CRM to kitchen inventory management, daily staff attendance, petty cash accounting, event checklisting, and vendor commission tracking.

---

## 🚀 Key Features

* **Multi-Tenant SaaS Architecture**: Dynamic onboarding setup wizard, isolated databases (tenant and branch level query scoping), and Stripe multi-currency subscriptions.
* **CRM & Bookings Manager**: Client referrals, availability engine, slot booking calendars, and custom packages builder.
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
* **Database**: MySQL / SQLite (for unit tests)
* **Integrations**: Stripe API (Subscriptions), FBR POS API

---

## 💻 Installation & Local Development

### 1. Prerequisites
Ensure you have the following installed locally:
* PHP 8.2 or higher
* Composer
* Node.js & NPM
* MySQL Server (WAMP/XAMPP or Docker)

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

Open `.env` and set your local database and Stripe parameters:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=marquee_cms
DB_USERNAME=root
DB_PASSWORD=

STRIPE_KEY=your-stripe-pk
STRIPE_SECRET=your-stripe-sk
STRIPE_WEBHOOK_SECRET=your-stripe-webhook-secret
```

Create database, run migrations, and seed default plans/roles:
```bash
php artisan migrate
php artisan db:seed
```

Generate the storage symlink:
```bash
php artisan storage:link
```

Compile assets and start the local development server:
```bash
npm run dev
php artisan serve
```

---

## 🔐 Default Login Information

For testing, seed the system defaults using the main database seeder which initializes the SaaS plans and basic administration credentials.

* **Super Admin Login (Central Platform)**:
  * **Email**: `admin@marqueecms.com` (or as configured in seeders)
  * **Password**: `password`

---

## ⚙️ Queue & Scheduler Configuration

### 1. Queues
MarqueeCMS uses background job queues to dispatch automated FBR POS sync requests, email templates, and invoice notifications.
* Local development: `QUEUE_CONNECTION=sync`
* Production: `QUEUE_CONNECTION=database` or `redis`

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
│   ├── Console/            # Commands & Scheduler
│   ├── Http/
│   │   ├── Controllers/    # CRM, Billing & Branch controllers
│   │   └── Middleware/     # Wizard checks & route security
│   ├── Livewire/           # Interactive components (Wizard, Finance, Booking)
│   ├── Models/             # Eloquent schema models (Booking, Recipe, Vendor, etc.)
│   ├── Services/           # RecipeService, FbrPosService, StripeBillingService
│   └── Traits/             # BelongsToTenant & BelongsToBranch isolation scopes
├── config/                 # Services and application configs
├── database/
│   ├── migrations/         # ERP database tables schemas
│   └── seeders/            # Plans and roles seeders
├── docs/                   # Full system architecture documentation
├── resources/
│   └── views/              # Blade layouts and subviews templates
├── routes/
│   └── web.php             # SaaS & tenant routes
└── tests/
    └── Feature/            # Automated test suite (127 tests)
```

---

## 📄 License
This project is licensed under the terms of the [MIT License](LICENSE).
