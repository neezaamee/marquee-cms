# Changelog — MarqueeCMS

All notable changes to the MarqueeCMS project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.9.0] - 2026-08-09
### Added
- Formulated Enterprise Vendor & Partnership Management Module (`vendor_services`, `vendor_commission_agreements`, `vendor_sales`, `vendor_settlements`, `vendor_ledgers` tables & migration).
- Extended `Vendor` model with auto-generated vendor codes (`VEN-000001`), contact profiles, tax NTN, bank accounts, payment terms, and running balance accessors.
- Developed `VendorCommissionService` domain calculation engine supporting 4 Commission Types (**Percentage**, **Fixed Per Event**, **Fixed Monthly**, **Hybrid** with min/max caps) and priority agreement resolution (Service-specific → Vendor-wide → None).
- Built Historical Commission Rate Preservation: Sales snapshot commission rates and agreement IDs at confirmation to permanently insulate past financial transactions from future contract changes.
- Integrated automated Accounting Journal Voucher posting (`Vendor Commission Income` revenue account and `Vendor Payable Clearing` liability account).
- Created Livewire components & views: `VendorDashboard`, `VendorManager`, `VendorDetail` (tabbed profile), `VendorServiceManager`, `VendorAgreementManager`, `VendorSaleManager`, `VendorLedgerView`, `VendorSettlementManager`, and `VendorReports` (5 printable report types).
- Integrated Vendor Services directly into `BookingView` with quick vendor assignment modal and live commission calculations.
- Created `VendorPartnershipModuleTest` feature test suite verifying registration, services, contracts, historical rate preservation, running balance ledgers, settlement payouts, accounting JV integration, and multi-tenant isolation (6/6 tests passing).

## [1.8.0] - 2026-08-08
### Added
- Formulated Kitchen Menu Slip Printing System integrated into Booking Management (`kitchen_print_logs` table & migration, `KitchenPrintLog` model).
- Added `department_id` foreign key link on `menu_categories` to support department-wise menu item grouping (`BBQ Station`, `Pakistani Kitchen`, `Tandoor & Bakery`, `Sweets & Desserts`, `Chinese & Continental`, `Other`).
- Developed printable A4 portrait Blade view `bookings.kitchen_slip` featuring 3 language options: **Bilingual (English + Urdu)** [Default], **English**, and **Urdu** (with RTL orientation & Unicode Noto Nastaliq Urdu / Noto Sans Arabic typography).
- Enforced complete financial transparency on Kitchen Slips (strictly excludes prices, subtotal, grand total, advance payments, balances, margins, and customer CNICs).
- Built Kitchen Slip Versioning (`V1`, `V2`) and Audit Log History tracking print timestamp, printer user ID, and language mode.
- Programmed Menu Modification Detection (`is_kitchen_menu_modified` accessor & menu hash computation) rendering an operational **Menu Modification Warning Alert** if booking items or guest counts change post-printing.
- Created `KitchenMenuSlipTest` feature test suite verifying route authorization, financial data omission, department grouping, version logs, menu modification alerts, and tenant isolation (5/5 tests passing).

## [1.7.0] - 2026-08-08
### Added
- Formulated Global Default Data Management System (`GlobalDefaultMaster` model and migration) establishing central master templates across 9 categories (Event Types, Menu Categories, Inventory Categories, Inventory Units, Expense Categories, Department Types, Vendor Types, Customer Types, Payment Methods).
- Developed Super Admin Global Default Manager portal (`/admin/global-defaults`) with category tab navigation, live search, status toggles, metrics overview, and CRUD modal controls.
- Developed Marquee Owner Master Data Portal (`/settings/default-data`) featuring a one-click "Import Missing Global Defaults" button cloning templates to tenant tables with `marquee_id = tenant_id`.
- Integrated automatic master template provisioning into `SetupWizard` to seed default records for newly registered tenants upon onboarding completion.
- Added Booking Readiness pre-requisite validation (`missingDependencies` check in `BookingWizard`) detecting unconfigured branches, halls, or event types and rendering actionable setup links.
- Created `GlobalDefaultDataSeeder` with standard production templates for Pakistani & Global Marquee management.
- Authored integration test suite `tests/Feature/GlobalDefaultDataTest.php` verifying template CRUD, onboarding auto-seeding, one-click importer, booking readiness banner, and tenant isolation.

## [1.6.0] - 2026-08-07
### Added
- Developed complete Department Management Module (`Department`, `DepartmentAttendance`, `DepartmentEmployee`, `DepartmentStockRequest`, `DepartmentStockIssue`, `DepartmentStockReturn`, `DepartmentStockLedger`, `DepartmentProduction` models and migrations).
- Built interactive Department Management Livewire components (`DepartmentDashboard`, `DepartmentManager`, `DepartmentEmployeeManager`, `DepartmentAttendanceManager`, `DepartmentRequestManager`, `DepartmentIssueManager`, `DepartmentReturnManager`, `DepartmentLedgerView`, `DepartmentProductionManager`, `DepartmentReports`).
- Enhanced Booking Management UI with interactive operational metric summary cards (Total Bookings, Confirmed, Tentative, Today's Events, Upcoming, This Month, Pending Approvals, Payment Outstanding) with `wire:click` filters.
- Segregated Booking Status from Guest Confirmation Headcounts (`tentative_guests`, `confirmed_guests`, `guest_status`).
- Added inline quick `Approve` and `Reject` actions for pending draft bookings with `BookingHistory` audit logging.
- Created test suites `DepartmentManagementTest`, `OperationalBookingDashboardTest`, and `GuestConfirmationTest`.

## [1.5.0] - 2026-08-06
### Added
- Developed Staff Attendance System (`Attendance` model and migration) enabling daily check-in/out and attendance status (Present, Absent, Late, Leave) logging for employees, fully isolated at the branch and tenant scopes.
- Created Catering Recipe System (`Recipe` and `RecipeDetail` models and migrations) mapping raw materials from inventory and defining ingredient quantities consumed per plate.
- Programmed per-head raw ingredient calculator (`RecipeService`) auto-computing required raw ingredient weights based on guest counts and customized booking or package menu items.
- Built Event Day Operations Checklist Tracker (`EventChecklist` model and migration) to coordinate setup, catering, decoration, and sound system prep tasks per booking.
- Structured Event Vendor Directory and Booking Commissions (`Vendor` and `VendorBooking` models and migrations) tracking contracted decorators, florists, and DJs, automatically computing agreed payback commission rates and values.
- Wrote integration test suites (`PendingTasksTest.php`) verifying all new models, relationships, tenant scopes, and service calculators.

## [1.4.0] - 2026-08-06
### Added
- Designed and implemented a modern, multi-step Initial Business Configuration Wizard (`SetupWizard` Livewire component) for newly registered tenants to capture Marquee details, Branch config, Hall details, Fiscal year, and operational defaults.
- Integrated frictionless automated data seeding (default "Day/Night" shift slots, system event types, hierarchical Chart of Accounts, catering menu categories/items, booking packages, inventory asset/payable configurations, default vendor, multi-currency settings, and expense category mappings) that executes dynamically upon wizard completion to guarantee all core ERP modules are fully operational out-of-the-box.
- Formulated `EnsureInitialSetupIsCompleted` middleware that strictly blocks unconfigured tenant users from operational modules (Bookings, Customers, Accounting, Inventory, Staff, etc.), redirecting them to the dashboard.
- Built a dynamic Setup Progress Widget on the main Dashboard listing configuration checklist status (Business profile, Branch setup, Hall venue, Financial Year, and Event Types) with quick links routing directly to corresponding wizard steps.
- Authored integration test suites (`InitialSetupWizardTest.php`) verifying guest blocking, middleware redirections, super admin exemptions, and step-by-step Livewire onboarding completions.

## [1.3.0] - 2026-08-06
### Added
- Developed `StripeBillingService` executing standard HTTP calls to create Stripe Checkout sessions and retrieve transaction logs securely.
- Created client-facing self-service Billing & Subscriptions dashboard (`TenantBilling` component, layouts, and views) for Marquee Owners to monitor plans, quotas, invoice history, and execute payments.
- Integrated multi-currency plan support, rendering prices, totals, and invoice items in local and foreign currencies (PKR, USD, EUR).
- Designed online checkout callback validation routes and controllers (`TenantBillingController`) fanning payments to `SaasPayment` records and extending tenant subscription dates by billing cycle months.
- Added comprehensive feature test suites (`SaasStripeBillingTest`) validating checkout session redirects, currency conversions, callback responses, and resource expiration offsets.
### Fixed
- Fixed Super Admin `marquee_id = null` type crashes in `InventoryService` code generation signatures and tenant billing portal layouts.

## [1.2.0] - 2026-08-05
### Added
- Developed FBR POS Synchronization Client (`FbrPosService.php`) that maps booking final bills to compliance endpoints and records validation outcomes (USIN, FBR invoice number, sync time).
- Integrated dynamic FBR compliance indicators and QR Codes generated in real time on printable voucher layouts (`booking-slip-v2.blade.php`).
- Formulated Laravel lifecycle triggers (`booted()` and `updated()`) in `Booking` model to capture status updates.
- Designed `BookingStatusNotification` dispatching HTML emails to customers upon reservation or confirmation changes.
- Embedded simulated SMS broadcasts that log payload details to system logs on status transitions.
- Authored feature test suites (`FbrPosSyncTest.php` and `BookingStatusAlertTest.php`) verifying compliance validations, HTTP mocking, and notification pipelines.

## [1.1.0] - 2026-08-05
### Added
- Created dynamic aggregates on dashboard (Total Bookings, Active Halls, Menu Packages, Monthly Revenue, and recent upcoming bookings list) to replace hardcoded placeholders.
- Added `getProfitAndLoss()` and `getBalanceSheet()` analytical methods to `AccountingService.php`.
- Created Profit & Loss statement report (Livewire component `finance.profit-loss` and routes/views), showing income items, expense items, and net margins.
- Created Balance Sheet statement report (Livewire component `finance.balance-sheet` and routes/views), showing assets, liabilities, owner equity, and balancing verification checks with period earnings integration.
- Created Customer CRM Referral Analytics report (Livewire component `customer-referral-analytics` and routes/views), showing group statistics for host sources, referred client count, booking frequency, and aggregate revenue.
- Added new report route endpoints and integrated navigation links in `sidebar.blade.php`.
- Wrote new feature test suites in `tests/Feature/AccountingReportsTest.php` and `tests/Feature/CustomerReferralAnalyticsTest.php` to verify all report logic and Livewire components.

## [1.0.0] - 2026-08-05
### Added
- Added "Total Bookings" column in Customer List UI, preloaded with eager-loaded bookings count optimization to prevent N+1 query loops.
- Extended Customer List search query to look up referred host names and referred host contact details (CRM referrer search).
- Redesigned Booking Slip (V2) layout by moving Event Type and Guest Count fields to the Event Venue & Timings section, moving Per Plate Rate summary to the bottom-left column, removing the redundant guest count column from the itemized table, and removing the email field from customer details.
- Integrated a venue-wide slot conflict lockout checking policy in `AvailabilityService`, ensuring a slot booked for one hall blocks slot assignment across all other halls in that venue.
- Implemented sorting controls (Up/Down buttons) for custom menu items in the booking wizard, edit page, and one-page wizard, persisting the customized order sequence via a new `sort_order` pivot table column.

### Fixed
- Fixed static calendar date validation checks in `BookingEnhancementsTest` to prevent errors from current local calendar progression.
- Fixed closed financial year validation tests in `AccountingRulesTest` by updating seeded records instead of duplicating them.

## [0.9.0] - 2026-08-05
### Added
- Created developer-first documentation folder `docs/`.
- Generated detailed project overview `docs/PROJECT_OVERVIEW.md` detailing system architecture.
- Documented model/component boundaries in `docs/MODULES.md`.
- Mapped database schemas and constraint inconsistencies in `docs/DATABASE.md`.
- Performed detailed Clean Architecture, Security, Performance, and UI/UX checks in `docs/ARCHITECTURE.md`.
- Formulated a prioritized development roadmap in `docs/ROADMAP.md`.
- Added developer tasks tracking ledger in `docs/TASKS.md`.

### Audited
- Checked multi-tenant `BelongsToTenant` trait query scoping logic.
- Checked branch-level data isolation via `BelongsToBranch` trait.
- Inspected custom RBAC pivot relationship boundaries (mismatch against user-assumed Spatie Permission).
- Evaluated `AvailabilityService` slot clash detection rules.
- Reviewed customer list UI pagination, identifying missing columns and referrer search gaps.
- Confirmed static dashboard statistics placeholders.
