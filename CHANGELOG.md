# Changelog

All notable changes to the **MarqueeCMS** project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
