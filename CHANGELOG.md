# Changelog

All notable changes to the **MarqueeCMS** project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.10.0] - 2026-09-18

### Added
- **Interactive Drag-and-Drop Booking Menu Reordering**:
  - Integrated `SortableJS` with Livewire 3 and Alpine.js across `BookingWizard`, `BookingOnePage`, and `BookingEdit`.
  - Added dedicated grip drag handles allowing operators to intuitively drag dishes up or down with the mouse.
- **Inline Dish Replacement and Contextual Insertion**:
  - Added interactive modal dialog allowing booking operators to swap an existing menu dish with an alternate dish or create a new custom dish on the fly directly from that dish's row.
  - Added contextual "Insert Dish Below" capability so dishes can be placed directly after any selected item without appending to the bottom of the table.
- **Automated Feature Test Coverage**:
  - Added `test_booking_slip_and_menu_reordering_and_replacement` feature test in `BookingManagementTest.php` covering slip rendering, zero-tax suppression, drag reordering, and modal-based dish replacement.

### Changed
- **Booking Slip Presentation Refinements**:
  - Standardized food menu display on `booking-slip.blade.php`, `booking-slip-v2.blade.php`, `booking-slip-v3.blade.php`, and `pdf.blade.php` to show English dish names only.
  - Suppressed the Tax Rate / Tax Amount line when tax percentage or amount is zero.
  - Cleaned up Event Venue & Timing header by removing the redundant "Branch:" prefix.

## [1.9.0] - 2026-09-17

### Added
- **Configurable Booking Slip Terms & Final Bill Conditions**:
  - Added dedicated database schema fields `booking_slip_terms` and `final_bill_conditions` (TEXT, nullable) to both `marquees` and `branches` tables via migration `2026_09_17_000001_add_terms_and_conditions_to_marquees_and_branches_tables`.
  - Integrated a **"Documentation Terms & Policy Conditions"** card into Step 3 ("Branch Operations Configuration") of the **Setup Wizard** (`setup-wizard.blade.php` and `SetupWizard.php`), pre-populated with standard marquee industry legal clauses while allowing full manual customization.
  - Added a dedicated **"Slip & Bill Terms"** tab and live editor within the Tenant Default Configuration Manager (`/settings/default-data`, `TenantDefaultManager.php` and `tenant-default-manager.blade.php`) enabling business owners to update terms and policies post-onboarding.
  - Updated standard Booking Slips (`booking-slip.blade.php`, `booking-slip-v2.blade.php`) to dynamically parse and render branch/marquee-specific booking terms with automatic fallback to system defaults.
  - Updated Final Bill Invoices (`final-bill-invoice-v2.blade.php`) to dynamically render branch/marquee-specific final settlement conditions and billing policies.
  - Added automated test suite `SetupWizardTermsAndConditionsTest` covering setup wizard defaults, persistence, booking slip display, and tenant default management.

## [1.8.0] - 2026-09-16

### Added
- **Super Admin Database Migrations & Live Site Maintenance Hub**:
  - Implemented a dedicated web-based database migration and system maintenance interface (`MigrationManager.php` and `migration-manager.blade.php`) under `/admin/migrations`.
  - Enables Super Admins to execute pending database schema updates (`php artisan migrate --force`) directly from the web interface on live production deployments without requiring SSH access.
  - Added pending and executed migration inspection, scanning `database/migrations` and comparing against the `migrations` table with batch tracking.
  - Added integrated Live Artisan Console Terminal viewer with execution timestamps, formatted output, and exit status logs.
  - Added one-click actions for system cache clearing (`optimize:clear`), global default data seeding (`GlobalDefaultDataSeeder`), and public storage link verification (`storage:link`).
  - Added Developer Tools sidebar navigation item and Super Admin dashboard quick maintenance shortcut.
  - Added automated test suite `SuperAdminMigrationManagerTest` with 10 feature test scenarios.

## [1.7.0] - 2026-09-15

### Added
- **Purchase & Procurement Dashboard**:
  - Implemented real-time analytical and operational dashboard (`PurchaseDashboard.php` and `purchase-dashboard.blade.php`) under `/purchases/dashboard`.
  - Added KPI summary cards: Total Purchases (MTD/YTD), Active Vendors, Pending POs, and Low Stock Alerts.
  - Implemented 6-month historical spending trend visualization using Chart.js.
  - Implemented Category-wise Spend breakdown donut chart with dynamic color palette.
  - Added PO pipeline status tracker (`Draft`, `Ordered`, `Partial`, `Received`, `Cancelled`) with direct status filtering.
  - Added critical inventory stock-level alerts with one-click "Create PO" direct action.
  - Added recent purchase orders and vendor performance quick-access tables.
- **Owner & Booking Officer Dedicated Dashboards**:
  - Enhanced Business Owner Dashboard with 5 new executive cards: **Total Sales**, **Total Purchases**, **Total Number of Guests**, **Bank Balance** (consolidated liquidity from COA & CashBankAccount), and **Cash in Hand** (vault & drawer balance).
  - Introduced dedicated **Booking Officer Operations Hub** mode: hides sensitive business financials (Operating Expenses, Net Margin, Purchases, Bank Balances) and prioritizes operational and guest intelligence (Total Guests to Host, Confirmed Bookings, Pending Inquiries / Follow-up, Today's Live Functions, 7-Day Pipeline, and Kitchen Slips Due).
  - Streamlined live function and upcoming pipeline tables to emphasize customer contact, headcount, package, and menu execution for booking officers.
  - Added preview toggle allowing Business Owners to seamlessly switch between the Executive Owner View and the Booking Officer View.

### Changed
- **Booking Slips Consolidation & Standardization**:
  - Deprecated legacy Booking Slip (V1) and (V3) layouts, establishing the comprehensive V2 design as the single application-wide standard **Booking Slip**.
  - Standardized UI actions in `BookingView` (`booking-view.blade.php`) and `BookingList` (`booking-list.blade.php`) from multiple versioned buttons (`V1`, `V2`, `V3`) to a single clean **"Print Booking Slip"** action.
  - Updated primary `booking-slip.blade.php` and `BookingSlip` Livewire component to adopt the complete multi-page pagination, QR code verification, and responsive venue timings layout.
  - Maintained transparent backwards compatibility for legacy `/bookings/{booking}/slip-v2` and `/bookings/{booking}/slip-v3` routes and components.

### Fixed
- **Department Requisition Item Relationship (`inventoryItem`)**:
  - Resolved `RelationNotFoundException: Call to undefined relationship [inventoryItem] on model [App\Models\DepartmentStockRequestItem]`.
  - Added backwards-compatible `inventoryItem()` Eloquent relationship alias across all department inventory item models:
    - `DepartmentStockRequestItem`
    - `DepartmentStockIssueItem`
    - `DepartmentStockReturnItem`
    - `DepartmentProductionItem`
  - Fixed view/details modal rendering in Department Requisitions.

- **Multi-Tenant Business Owner Single Branch Assignment**:
  - Fixed branch selection dropdown showing empty when a newly created Business Owner with a single main branch assigns an employee to a branch.
  - Updated `StaffForm`, `StaffController`, `UserForm`, `StaffList`, and `DepartmentEmployeeManager` to automatically resolve and pre-select the tenant's primary branch when only one branch exists.
  - Added branch creation prompt and validation guidance if no branches exist for a marquee.

- **Production Logo Asset Resolution**:
  - Resolved missing logo issue on live deployment environments lacking symlink or direct public storage access.
  - Implemented `/storage/{path}` fallback route in `routes/web.php` with proper MIME type headers and cache control.
  - Enhanced `Marquee::getLogoUrlAttribute()` to reliably resolve logo paths across local and cloud environments.

---

## [1.6.0] - 2026-09-09

### Added
- **Booking Manager Role & Dedicated PRA / FBR Invoice Posting**:
  - Registered `post_final_bill_pra` permission in `permissions` table and assigned to `business_owner`, `owner`, `branch_manager`, and `booking_manager` roles via migration `2026_09_09_200000_add_booking_manager_role_and_pra_permissions.php`.
  - Configured `booking_manager` / `booking_manager_pra` with complete booking operational privileges (`view_bookings`, `create_bookings`, `edit_bookings`, `cancel_bookings`, `view_halls`, `view_menus`, `view_packages`, `event-types.view`, `view_payments`, `create_payments`, `view_reports`, `post_final_bill_pra`).
  - Updated `BookingPolicy` to allow booking managers to manage and cancel bookings within their marquee/branch scope.
  - Added dedicated **PRA / FBR POS Sync Card** in `BookingView` with real-time sync status badges (`Posted`, `Pending`, `Failed`), registered Invoice # display, and interactive **"Post Final Invoice to PRA/FBR"** / **"Re-sync"** button with loading indicators.
  - Added `postInvoiceToPraFbr()` method in `BookingView` component with real-time session feedback.
  - Updated Final Bill modal action to **"Lock & Post to PRA/FBR"**.

- **Dynamic Custom Roles in Employee Designations**:
  - Added `Employee::getDesignations()` method that dynamically merges static staff designations with custom roles created by SuperAdmin from the `roles` table.
  - Implemented case-insensitive deduplication and natural sorting while maintaining security isolation by excluding platform superadmin and owner roles (`super_admin`, `business_owner`, `owner`).
  - Preserves employee's current designation when editing profiles even if customized.
  - Synchronized designation listings across `StaffForm`, `StaffController`, `StaffList`, `DepartmentEmployeeManager`, and `UserForm`.

### Changed
- **PRA Invoice QR Code Scan Optimization**:
  - Updated PRA invoice QR code generation in `FbrPosService` and `FinalBillInvoiceV2` so scanning the QR code displays strictly the **PRA invoice number** directly rather than a web URL.
  - Sanitized existing stored invoices in database.

- **Roles & Permissions Manager Modal Fix**:
  - Fixed modal opening and closing behavior for "Add New Role" and "Add New Permission" buttons in `roles-manager.blade.php` and `permissions-manager.blade.php` adhering to Falcon Admin theme and Livewire 3 modal state management.

---

## [1.5.0] - 2026-09-08

### Added
- **Punjab Revenue Authority (PRA) e-IMS Integration**:
  - Implemented dual-mode POS synchronization supporting both **Cloud Web API** (`https://ims.pral.com.pk/ims/`) and **Local Fiscal Agent** (`http://localhost:8524`) connection types per the official PRAL January 2026 Manual.
  - Added `pos_connection_type` column (`'cloud'` | `'local'`) to `branches` table via migration `2026_09_07_220000_add_pos_connection_type_to_branches_table.php`.
  - Extended `FbrPosService` with regional authority routing: dispatches to `syncPraInvoice()` when the marquee province is **Punjab** / authority is **PRA**; falls back to existing FBR flow for all other regions.
  - Added PRA-specific line-item PCT Code `99010000` (marriage hall / banquet) and success verification parsing (`Code: 100`) extracting `InvoiceNumber` and building the official PRA QR verification URL: `https://e.pra.punjab.gov.pk/VerifyInvoice?InvoiceNo=...`.
  - Sandbox mode auto-uses the official PRAL testing token when only the 8-character desktop access code is present.

- **Branch POS Connection Type UI**:
  - Added `pos_connection_type` dropdown to `BranchForm` Livewire component and `branch-form.blade.php` with live `wire:model` binding, validation, and dynamic tax-authority headings and field placeholders.
  - `BranchList` updated to display the active connection type badge.

- **Customer Final Bill Invoice — PRA Support**:
  - `FinalBillInvoiceV2` dynamically labels the invoice header as **PRA Invoice #** or **FBR Invoice #** based on the active regional authority.
  - Generates QR code targeting the official PRA verification portal.
  - Displays **PRA POS Registered** badge and **Verified Synced** confirmation mark.
  - Added vector SVG logo for Punjab Revenue Authority (`public/assets/img/logos/pra-logo.svg`).

- **Tax Configuration UI**:
  - `TaxConfiguration` Livewire component now renders dynamic authority labels (`PRA POS ID`, `PRA Access Code / Token`) and an **Integration Method** dropdown (`Cloud Web API` / `Local Agent 8524`).

- **Automated Test Coverage**:
  - `PraPosSyncTest.php` (4 tests): Cloud Web API sync, Local Agent mode, error response capturing, and network failure/timeout handling.
  - `BusinessOwnerBranchAndTaxAccessTest.php`: Full Business Owner branch and tax configuration access suite.
  - All 32 tests in the full branch & access suite pass (129 assertions).

### Changed
- `MarqueeList` component updated to surface PRA-related metadata on the tenant listing screen.
- `CrudAccessTest` extended with POS connection type assertions.

---

## [1.4.0] - 2026-09-05

### Added
- **CRM & Lead Inquiries Pipeline**:
  - Implemented `Lead` and `LeadActivity` models with full database migrations (`2026_09_05_000001_create_leads_and_lead_activities_tables.php`).
  - Added `LeadManager` Livewire component adhering to the Falcon UI design system with real-time status filtering (New, Contacted, Qualified, Proposal Sent, Won, Lost), activity logging, and fast conversion into bookings.
  - Added full test coverage in `tests/Feature/ModuleOperationalAuditFixesTest.php`.

- **All Users Activity Log & Audit Trail**:
  - Created centralized enterprise audit log viewer (`ActivityLogManager.php` and `activity-log-manager.blade.php`) styled with Falcon Admin components.
  - Added role-based access control: Super Admin can monitor system-wide activity, while Business Owners and Managers can audit staff actions across their tenant/branches.
  - Captured event types, actor IDs, IP addresses, user agents, affected models, and detailed metadata changes.
  - Added automated feature tests in `tests/Feature/ActivityLogTest.php`.

- **Final Bill Sales Tax Invoice V2**:
  - Implemented `FinalBillInvoiceV2` Livewire component and Blade views (`final-bill-invoice-v2.blade.php`, `final_bill_v2.blade.php`).
  - Styled with Falcon Admin design studio layout: dynamic branch logo and address headers, space-optimized 2-column event and customer specifications, dynamic FBR invoice numbering, and real-time QR code generation.
  - Integrated multi-paper print dialogue allowing seamless zero-scale printing across A4, Letter, and Legal paper sizes.
  - Added comprehensive test coverage in `tests/Feature/FinalBillInvoiceV2Test.php`.

- **Two-Stage Payment & Ledger Posting Workflow**:
  - Added `pending_posting`, `posted`, and `rejected` payment statuses with tracking fields (`posting_journal_voucher_id`, `posted_by`, `posted_at`, `posting_notes`) via migration `2026_09_03_000001_add_two_stage_posting_columns_to_booking_payments_table.php`.
  - Implemented two-stage payment collection: Front Desk staff record customer deposits, while Accountants review and post payments directly into general ledger accounts.
  - Added test coverage in `tests/Feature/TwoStagePaymentWorkflowTest.php`.

- **Supplier Category Hierarchy & Procurement Management**:
  - Added `SupplierCategory` model, migration `2026_09_03_000002_create_supplier_categories_tables.php`, and `SupplierCategoryList` Livewire component.
  - Grouped procurement vendors into customizable expense and inventory categories.
  - Added test coverage in `tests/Feature/SupplierCategoryManagementTest.php`.

- **Global Asynchronous Livewire Progress Loader**:
  - Added a responsive animated top progress bar and floating Livewire indicator to `resources/views/layouts/admin.blade.php` providing visual feedback during asynchronous network operations.

### Changed
- Standardized booking view dates formatting and vendor sales calculations.
- Hardened multi-tenant global Chart of Accounts and Financial Year provisioning via migration `2026_09_03_000003_ensure_all_tenants_have_default_coa_and_fy.php`.

---

## [1.3.0] - 2026-09-02

### Added
- **Double-Entry Financial Accounting & COA Integration**:
  - Integrated automated Chart of Accounts (COA) generation and Journal Voucher posting upon booking confirmation and payment receipt via `BookingFinancialService`.
  - Added real-time Customer Ledger tracking via `CustomerLedger` model and migrations (`2026_08_31_100001_add_financial_integration_columns_to_bookings_and_payments_tables.php`, `2026_08_31_100002_create_customer_ledgers_table.php`).
  - Added Customer Advance Liability report (`CustomerAdvanceLiabilityReport.php`) tracking unearned event revenue vs recognized revenue upon event completion.
  - Implemented `RevenueRecognitionService` to automate recognition of customer advances upon event execution.
  - Added `CashBankAccountFactory`, `AccountFactory`, and `JournalVoucherFactory` for end-to-end accounting test coverage.

- **Synthetic Demo Data Generation Suite**:
  - Implemented `SyntheticDataGeneratorService` with realistic localized fake data generators for tenants, branches, halls, bookings, menu items, inventory, employees, and financial transactions.
  - Added `php artisan app:generate-synthetic-data` Artisan console command.
  - Created Super Admin interactive portal UI (`DemoDataGenerator.php` and `SuperAdminDashboard.php`).

- **Phone Number Normalization & Validation Service**:
  - Created `PhoneNumberService` to automatically sanitize, validate, and normalize Pakistani local format (`03XX-XXXXXXX`) and international E.164 formats across all customer, owner, vendor, and staff records.
  - Added migration `2026_08_29_171314_normalize_existing_phone_numbers.php` to backfill existing records.
  - Added complete unit test suite in `PhoneNumberServiceTest.php`.

- **Multi-Branch & Hall Scoping & Configuration**:
  - Added branch-level tax rate management (FBR, PRA, SRB) and custom booking reference prefixes via migration `2026_08_29_000000_add_tax_rate_and_prefixes_to_branches_table.php`.
  - Added `TaxConfiguration.php` Livewire component and administration views.
  - Added `UnitConversionList.php` for kitchen inventory units conversions.
  - Extended multi-branch booking test suite with full test coverage (`MultiBranchBookingScopeTest.php`, `BranchHallConfigurationTest.php`, `BookingPaymentPostingIntegrationTest.php`).

- **Vendor Advance & Payment Tracking Integration**:
  - Added advance tracking, customer advance deductions, and invoice inclusion flags to vendor sales (`VendorSaleManager.php`, `VendorAdvanceAndBalanceIntegrationTest.php`).

### Changed
- Refactored `BookingWizard`, `BookingOnePage`, `BookingEdit`, and `BookingView` components for seamless double-entry ledger hooks.
- Enhanced `Dashboard` summary cards with dynamic accounting ledger balances and real-time revenue stats.

---

## [1.2.0] - 2026-08-11

### Added
- **Booking Privacy & Partition Configuration**:
  - Implemented dynamic "Privacy / Partition Required?" configuration to the Booking Management module.
  - Dynamically shows/hides Ladies and Gents percentage controls when checking/unchecking the option.
  - Implemented Livewire 3 server-side validation ensuring ratios sum to exactly 100% and fall between 0% and 100%.
  - Added new columns `privacy_required`, `privacy_ladies_percentage`, and `privacy_gents_percentage` to the `bookings` table via migration `2026_08_11_000001_add_privacy_fields_to_bookings_table.php`.
  - Displayed guest arrangements in the booking details screen (`booking-view.blade.php`).
  - Added `Privacy / Partition` configuration printing on all 3 versions of printable Reservation Slips (`booking-slip.blade.php`, `booking-slip-v2.blade.php`, and `booking-slip-v3.blade.php`).
  - Added full test coverage for the privacy workflow in `BookingManagementTest.php`.

### Changed
- **Falcon Template Layout Integration & Modal Removal**:
  - Replaced Bootstrap Modal dialogs with inline card-based forms adopting the Falcon Template layout design across the entire Service Providers module.
  - Applied the change to:
    - Service provider profile creation/edit (`vendor-manager.blade.php`)
    - Service catalog items addition/edit (`vendor-service-manager.blade.php`)
    - Commission agreements configuration (`vendor-agreement-manager.blade.php`)
    - Sale registration journal (`vendor-sale-manager.blade.php`)
    - Settlement clearances payout (`vendor-settlement-manager.blade.php`)
- **Service Providers Module Rebranding & Security Audit**:
  - Rebranded the "Vendors" module to "Service Providers" (case-sensitive) across all view files, controllers, route parameters, and navigation menus.
  - Audited multi-tenant isolation security inside all Service Provider Livewire components (`VendorManager.php`, `VendorServiceManager.php`, `VendorAgreementManager.php`, `VendorSaleManager.php`, `VendorSettlementManager.php`, `VendorLedgerView.php`, `VendorReports.php`) to block cross-tenant ID spoofing.
  - Updated commission agreements validation rules to make `commission_percentage`, `fixed_commission_amount`, and `monthly_fixed_amount` `nullable` so users can choose single fee configurations.
  - Corrected outstanding payable metrics calculation on the dashboard to sum the live `current_balance` attribute.
  - Verified and secured the test coverage in `VendorPartnershipModuleTest.php`.
