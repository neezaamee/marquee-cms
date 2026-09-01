# Changelog

All notable changes to the **MarqueeCMS** project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
