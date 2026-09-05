# MarqueeCMS — Enterprise Multi-Tenant Marquee & Banquet ERP SaaS

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20.svg?style=flat&logo=laravel)](https://laravel.com)
[![Livewire](https://img.shields.io/badge/Livewire-3.x-FB70A9.svg?style=flat&logo=livewire)](https://livewire.laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%20%7C%208.3-777BB4.svg?style=flat&logo=php)](https://www.php.net)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3.svg?style=flat&logo=bootstrap)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

**MarqueeCMS** is a comprehensive, enterprise-grade Multi-Tenant Software-as-a-Service (SaaS) ERP designed specifically for marquee owners, wedding hall operators, banquet managers, and catering businesses. It provides an end-to-end operational workflow covering reservation scheduling, guest confirmation tracking, double-entry financial accounting, customer ledger liabilities, global default master data provisioning, kitchen inventory management, department stock requests/issues/productions, daily staff attendance, petty cash accounting, event checklisting, and vendor commission tracking.

---

## 🚀 Key Features

* **Multi-Tenant SaaS Architecture**: Dynamic onboarding setup wizard, isolated databases (tenant and branch level query scoping with `BelongsToTenant` and `BelongsToBranch`), and Stripe multi-currency subscriptions.
* **Double-Entry Financial Accounting**: Automated Chart of Accounts (COA) provisioning, automated Journal Voucher generation on booking creation and payment posting, Trial Balance, Profit & Loss, and Balance Sheet generation.
* **Customer Ledger & Advance Liabilities**: Real-time customer balance tracking, advance payment reconciliation against actual events, and automated liability reporting (`CustomerAdvanceLiabilityReport`).
* **Two-Stage Payment & Ledger Posting**: Separation of front-desk customer payment collection (`pending_posting`) and accountant verification/posting to financial accounts.
* **Leads & Inquiries CRM**: Full sales lead lifecycle tracking (New, Contacted, Qualified, Proposal Sent, Won, Lost), lead interaction logs, and instant conversion into active bookings.
* **All Users Activity Log & Audit Trail**: Centralized enterprise audit trail tracking user authentication, record mutations, client IP addresses, user agents, and tenant scoping.
* **Sales Tax Invoice V2 (Falcon Print Studio)**: Enterprise multi-paper printable invoices (A4, Letter, Legal) with FBR digital invoice numbers, QR code generation, branch branding, and space-optimized event specifications.
* **Vendor & Service Provider Partnerships**: Vendor profiles, service catalogs, commission agreements, sale registrations, advance payouts, and automatic deduction workflows.
* **Synthetic Demo Data Generator**: Built-in `SyntheticDataGeneratorService` and `app:generate-synthetic-data` Artisan command to provision realistic multi-branch demo data in seconds.
* **Automated Phone Normalization**: Integrated `PhoneNumberService` standardizing local Pakistani formats (`03XX-XXXXXXX`) and E.164 international numbers across all forms.
* **Multi-Branch & Hall Scoping**: Branch-isolated operational capacities, hall slot allocations (Morning/Evening/Full Day), customizable tax rates (FBR/PRA/SRB), and booking reference prefixes.
* **Operational Booking Dashboard**: Real-time DB summary cards (Total Bookings, Confirmed, Tentative, Today's Events, Upcoming, Pending Approvals, Payment Outstanding) with `wire:click` automatic filtering.
* **Department Management Module**: Complete department hierarchy (Kitchen, Housekeeping, Accounts, IT, Procurement), employee assignments, daily attendance, stock requests, stock issues, stock returns, and production logs.
* **Kitchen & Catering Recipes**: Linked dish menus to raw inventory items, with automated per-head material requirement calculations (`RecipeService`).
* **HR & Staff Attendance**: Employees directory, monthly payroll overview, and branch-isolated daily check-in/out attendance logging.
* **Regional Integrations**: FBR (Federal Board of Revenue) sandbox POS synchronization client with QR codes verification.

---

## 🛠️ Technology Stack

| Component | Technology |
| :--- | :--- |
| **Backend Framework** | [Laravel 12](https://laravel.com) (PHP 8.2 / 8.3) |
| **Frontend Framework** | [Livewire 3](https://livewire.laravel.com) / Alpine.js |
| **UI Design System** | [Falcon Admin Template](falcon/) & [Bootstrap 5.3](https://getbootstrap.com) |
| **Styling Pipeline** | [Tailwind CSS 4](https://tailwindcss.com) + Sass |
| **Authorization** | [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission) |
| **Database** | MySQL 8.0+ / MariaDB 10.4+ / SQLite (Tests) |
| **PDF Generation** | [Barryvdh Laravel DomPDF](https://github.com/barryvdh/laravel-dompdf) |
| **Payments** | [Stripe SDK](https://stripe.com) |

---

## 💻 Installation & Local Development

### 1. Prerequisites
Ensure the following are installed locally:
* **PHP**: 8.2 or 8.3 with extensions (`pdo_mysql`, `mbstring`, `openssl`, `gd`, `bcmath`, `curl`, `zip`)
* **Composer**: 2.x
* **Node.js**: 18.x or 20.x & **NPM**
* **MySQL**: 8.0+ / WAMP / XAMPP / Laragon

### 2. Step-by-Step Setup

1. **Clone the repository**:
   ```bash
   git clone https://github.com/neezaamee/marquee-cms.git
   cd marquee-cms
   ```

2. **Install Composer & NPM dependencies**:
   ```bash
   composer install
   npm install
   ```

3. **Configure the Environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Set Database Credentials in `.env`**:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=marqueecms
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Run Migrations & Seed Default Data**:
   ```bash
   php artisan migrate --seed
   ```

6. **Generate Public Storage Symlink**:
   ```bash
   php artisan storage:link
   ```

7. **Compile Assets & Start Servers**:
   ```bash
   npm run dev
   php artisan serve
   ```
   Access the application at `http://127.0.0.1:8000`.

---

## 🧪 Synthetic Demo Data Generation

To quickly populate an empty tenant with comprehensive operational data (Branches, Halls, Bookings, Customers, Payments, Journal Vouchers, Inventory, and Staff):

```bash
php artisan app:generate-synthetic-data {tenant_id}
```
Or use the Super Admin interactive generator at `/admin/demo-data-generator`.

---

## 🔐 Default Login Credentials

After running `php artisan db:seed`, the following default accounts are available:

| Role | Email | Password | Scope |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `superadmin@elaftech.com` | `Password123!` | Platform Central Portal |
| **Marquee Tenant Owner** | `ghulamabbas@thesheraton.com` | `Password123!` | The Sheraton Marquee |
| **Branch Manager** | `manager@thesheraton.com` | `Password123!` | Gulberg Branch |

---

## ⚙️ Queue & Scheduler Configuration

### Background Queues
Background jobs handle FBR POS synchronization, email notifications, and PDF receipt rendering:
```bash
php artisan queue:work
```

### Task Scheduler
For recurring subscription checks, advance revenue recognition, and ledger reconciliation, configure a CRON job:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📂 Project Directory Structure

```text
├── app/
│   ├── Console/Commands/   # CLI commands (GenerateSyntheticDataCommand)
│   ├── Http/Controllers/   # SaaS Billing, Export & PDF controllers
│   ├── Livewire/           # Livewire 3 Components (SuperAdmin, Owner, Bookings, Finance)
│   ├── Models/             # Eloquent Models (Booking, CustomerLedger, JournalVoucher, etc.)
│   ├── Services/           # AccountingService, BookingFinancialService, PhoneNumberService
│   └── Traits/             # BelongsToTenant & BelongsToBranch Multi-Tenant Scopes
├── config/                 # Application configuration files
├── database/
│   ├── factories/          # Eloquent model factories for testing & synthetic data
│   ├── migrations/         # Database table schema definitions
│   └── seeders/            # Database seeders (Roles, Permissions, Defaults, Demo)
├── resources/
│   └── views/              # Blade layouts & Falcon Admin template views
├── routes/
│   └── web.php             # Application and Livewire routes
└── tests/
    ├── Feature/            # Comprehensive Feature test suites (300+ tests)
    └── Unit/               # Unit test suites (Services & Utilities)
```

---

## 🧪 Running Automated Tests

MarqueeCMS includes a comprehensive automated test suite with over 300 unit and feature tests:

```bash
php artisan test
```

---

## 📄 License & Security

- **License**: Licensed under the [MIT License](LICENSE).
- **Security Policy**: See [SECURITY.md](SECURITY.md) for vulnerability disclosure and reporting guidelines.
