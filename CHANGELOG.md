# Changelog

All notable changes to the **MarqueeCMS** project will be documented in this file.

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
