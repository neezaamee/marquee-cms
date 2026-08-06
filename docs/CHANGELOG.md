# Changelog — MarqueeCMS

All notable changes to the MarqueeCMS project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

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
