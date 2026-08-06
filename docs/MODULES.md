# Module Inventory — MarqueeCMS

This document provides a comprehensive inventory of all functional and administrative modules within MarqueeCMS, verifying their implementation status and listing the technical components backing them.

---

## 1. SaaS & Multi-Tenant Management
* **Status**: Completed
* **Completion**: 95%
* **Description**: Handles subscription plans, tenant registration (Marquee companies), SaaS billing cycles, SaaS invoice generation, and super-admin payment processing.
* **Database Tables**:
  - `subscription_plans`: Plan details, price, intervals.
  - `marquees`: Company details, plan relationships, trial end dates.
  - `plan_features`: List of plan privileges.
  - `saas_invoices`: Invoices sent to tenants.
  - `saas_payments`: Payments received from tenants.
* **Livewire Components**:
  - [PlansList](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/PlansList.php)
  - [PlanForm](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/PlanForm.php)
  - [FeaturesList](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/FeaturesList.php)
  - [FeatureForm](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/FeatureForm.php)
  - [FeatureMatrix](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/FeatureMatrix.php)
  - [BillingCyclesList](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/BillingCyclesList.php)
  - [BillingCycleForm](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/BillingCycleForm.php)
  - [SaasInvoicesList](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/SaasInvoicesList.php)
  - [SaasInvoiceDetail](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/SaasInvoiceDetail.php)
  - [SaasPaymentsList](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/SaasPaymentsList.php)
  - [SaasPaymentForm](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/SaasPaymentForm.php)
* **Models**:
  - [Marquee](file:///c:/wamp64/www/MarqueeCMS/app/Models/Marquee.php)
  - [SubscriptionPlan](file:///c:/wamp64/www/MarqueeCMS/app/Models/SubscriptionPlan.php)
  - [PlanFeature](file:///c:/wamp64/www/MarqueeCMS/app/Models/PlanFeature.php)
  - [BillingCycle](file:///c:/wamp64/www/MarqueeCMS/app/Models/BillingCycle.php)
  - [SaasInvoice](file:///c:/wamp64/www/MarqueeCMS/app/Models/SaasInvoice.php)
  - [SaasPayment](file:///c:/wamp64/www/MarqueeCMS/app/Models/SaasPayment.php)
* **Routes**: Resourceful mappings under `routes/web.php` for `subscription-plans`, `plan-features`, `billing-cycles`, `saas-invoices`, `saas-payments`.
* **Permissions**: Access is restricted strictly to SaaS Super Admin users (`isSuperAdmin()`).
* **Issues Found**: Branch FBR configuration settings are stored inside individual branches rather than having central SaaS integration configurations.
* **Recommendations**: Centralize billing settings so that Super Admins can customize trial lengths and tax defaults centrally.

---

## 2. Branch & Hall Operations
* **Status**: Completed
* **Completion**: 95%
* **Description**: Setup of operational branches, physical banquet halls/venues, shift slots (e.g. morning/evening shift durations), and hall-slot assignments.
* **Database Tables**:
  - `branches`: Branches under a tenant (e.g. Lahore Gulberg, Karachi DHA). Includes FBR/PRA integration keys.
  - `halls`: Physical halls with capacities.
  - `slots`: Morning, Afternoon, Evening schedule shift hours.
  - `hall_slots`: Pivot table assignment of slots to specific halls.
* **Livewire Components**:
  - [BranchList](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/BranchList.php)
  - [BranchForm](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/BranchForm.php)
  - [HallList](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/HallList.php)
  - [HallForm](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/HallForm.php)
  - [SlotList](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/SlotList.php)
  - [SlotForm](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/SlotForm.php)
  - [HallSlotAssignment](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/HallSlotAssignment.php)
* **Models**:
  - [Branch](file:///c:/wamp64/www/MarqueeCMS/app/Models/Branch.php)
  - [Hall](file:///c:/wamp64/www/MarqueeCMS/app/Models/Hall.php)
  - [Slot](file:///c:/wamp64/www/MarqueeCMS/app/Models/Slot.php)
  - [HallSlot](file:///c:/wamp64/www/MarqueeCMS/app/Models/HallSlot.php)
* **Routes**: `/branches`, `/halls`, `/slots`, `/hall-slots`
* **Permissions**: `manage_settings` required.
* **Issues Found**:
  - If a slot is assigned to a hall for a date, the conflict checker does not prevent assigning it to a different hall (venue-wide slot booking rule).
* **Recommendations**: Add a customizable constraint toggle to enforce that if a slot is assigned to *any* hall, it restricts the slot from being booked for other halls (venue-wide bookings).

---

## 3. Staff & User Access Management
* **Status**: Completed
* **Completion**: 90%
* **Description**: Registers company employees, handles employee system logins (User model linkage), and manages roles and permissions pivot boundaries.
* **Database Tables**:
  - `employees`: Staff metadata (name, salary, contact, designations).
  - `users`: Credentials, status, role assignments.
  - `roles`: Role definitions (e.g. `owner`, `branch_manager`, `accountant`).
  - `permissions`: Permission tags.
  - `permission_role`: Pivot linkage for authorization.
* **Livewire Components**:
  - [StaffList](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/StaffList.php)
  - [StaffForm](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/StaffForm.php)
  - [UserList](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/UserList.php)
  - [UserForm](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/UserForm.php)
  - [ManageStaffLogins](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/ManageStaffLogins.php)
* **Models**:
  - [Employee](file:///c:/wamp64/www/MarqueeCMS/app/Models/Employee.php)
  - [User](file:///c:/wamp64/www/MarqueeCMS/app/Models/User.php)
  - [Role](file:///c:/wamp64/www/MarqueeCMS/app/Models/Role.php)
  - [Permission](file:///c:/wamp64/www/MarqueeCMS/app/Models/Permission.php)
* **Routes**: `/staff`, `/users`, `/staff/{staff}/logins`
* **Permissions**: `manage_staff` required.
* **Issues Found**:
  - Code comments and settings assumed Spatie Permission was installed. However, it uses a custom pivot implementation.
* **Recommendations**: Clean up obsolete Spatie comments, document the custom RBAC query check, and add an admin screen to manage role permissions pivot mappings directly.

---

## 4. Customer CRM & Referrals
* **Status**: Partially Complete
* **Completion**: 75%
* **Description**: Tracks customer profiles (Individuals/Corporates), manages uploads of legal verification documents (CNIC/NTN), and logs communication records (calls, visits).
* **Database Tables**:
  - `customers`: CNIC, NTN, contacts, types, referral records.
  - `customer_documents`: Files uploaded for proof of identity.
  - `customer_communication_logs`: Communication histories.
* **Livewire Components**:
  - [CustomerList](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/CustomerList.php)
  - [CustomerForm](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/CustomerForm.php)
  - [CustomerProfile](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/CustomerProfile.php)
* **Models**:
  - [Customer](file:///c:/wamp64/www/MarqueeCMS/app/Models/Customer.php)
  - [CustomerDocument](file:///c:/wamp64/www/MarqueeCMS/app/Models/CustomerDocument.php)
  - [CustomerCommunicationLog](file:///c:/wamp64/www/MarqueeCMS/app/Models/CustomerCommunicationLog.php)
* **Routes**: `/customers`
* **Permissions**: `create_bookings`, `edit_bookings` (shared).
* **Issues Found**:
  - **"Total Bookings"** column is missing from the customer list table.
  - Referred by name/contact is not searchable.
  - Referral booking reports are missing entirely.
* **Recommendations**: Update `customer-list.blade.php` to display total bookings, integrate referrer search, and add a simple CRM report for referral metrics.

---

## 5. Catering & Menu Packages Builder
* **Status**: Completed
* **Completion**: 90%
* **Description**: Catalogs menu dishes, plate rates, custom catering categories, and sets up seasonal menu packages. Includes a Package Builder interface.
* **Database Tables**:
  - `menu_categories`: Categories (e.g. Starters, Main Course, Desserts).
  - `menu_items`: Dishes, base cost, Urdu names.
  - `packages`: Named packages (e.g., Gold Package, VIP Package).
  - `package_menu_items`: Pivot linking dishes to packages.
* **Livewire Components**:
  - [MenuCategoryList](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/MenuCategoryList.php)
  - [MenuCategoryForm](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/MenuCategoryForm.php)
  - [MenuItemList](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/MenuItemList.php)
  - [MenuItemForm](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/MenuItemForm.php)
  - [PackageList](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/PackageList.php)
  - [PackageForm](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/PackageForm.php)
  - [PackageBuilder](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/PackageBuilder.php)
  - [PackagePreview](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/PackagePreview.php)
* **Models**:
  - [MenuCategory](file:///c:/wamp64/www/MarqueeCMS/app/Models/MenuCategory.php)
  - [MenuItem](file:///c:/wamp64/www/MarqueeCMS/app/Models/MenuItem.php)
  - [Package](file:///c:/wamp64/www/MarqueeCMS/app/Models/Package.php)
* **Routes**: `/menu-categories`, `/menu-items`, `/packages`, `/packages/{package}/builder`, `/packages/{package}/preview`
* **Permissions**: `view_menus`, `view_packages`
* **Issues Found**:
  - Dish items sequence ordering is not customizable.
  - Urdu name field exists in DB but is not exposed with an Urdu-keyboard input in the UI.
* **Recommendations**: Add a sequence ordering drag-and-drop or index field for menu items, and add Urdu validation helper checks.

---

## 6. Booking Operations & Pricing Engine
* **Status**: Partially Complete
* **Completion**: 80%
* **Description**: Booking workflow, including a Multi-step wizard and a One-page rapid booking interface. Integrates an availability checking engine, billing calculations, and printable invoices.
* **Database Tables**:
  - `bookings`: Booking metadata, totals, status flags.
  - `booking_histories`: Status transition audits.
  - `booking_halls`: Pivot table mapping multiple halls to a booking.
  - `booking_menu_items`: Selected dishes for the booking.
  - `booking_extra_services`: Selected addons (stage, lighting).
  - `booking_payments`: Customer payments ledger.
  - `booking_final_bills`: Event-day billing adjustments.
* **Livewire Components**:
  - [BookingList](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/BookingList.php)
  - [BookingView](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/BookingView.php)
  - [BookingEdit](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/BookingEdit.php)
  - [BookingWizard](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/BookingWizard.php)
  - [BookingOnePage](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/BookingOnePage.php)
  - [BookingSlip](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/BookingSlip.php)
  - [BookingSlipV2](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/BookingSlipV2.php)
  - [BookingSlipV3](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/BookingSlipV3.php)
* **Models**:
  - [Booking](file:///c:/wamp64/www/MarqueeCMS/app/Models/Booking.php)
  - [BookingHistory](file:///c:/wamp64/www/MarqueeCMS/app/Models/BookingHistory.php)
  - [BookingMenuItem](file:///c:/wamp64/www/MarqueeCMS/app/Models/BookingMenuItem.php)
  - [BookingPayment](file:///c:/wamp64/www/MarqueeCMS/app/Models/BookingPayment.php)
  - [BookingExtraService](file:///c:/wamp64/www/MarqueeCMS/app/Models/BookingExtraService.php)
  - [BookingFinalBill](file:///c:/wamp64/www/MarqueeCMS/app/Models/BookingFinalBill.php)
* **Routes**: `/bookings`, `/bookings/{booking}/slip`, `/bookings/{booking}/slip-v2`, `/bookings/{booking}/slip-v3`, `/bookings/{booking}/pdf`
* **Services**:
  - [AvailabilityService](file:///c:/wamp64/www/MarqueeCMS/app/Services/AvailabilityService.php) (DateTime boundary check)
  - [BookingPricingService](file:///c:/wamp64/www/MarqueeCMS/app/Services/BookingPricingService.php) (Subtotal, tax, addon calculations)
* **Permissions**: `view_bookings`, `create_bookings`, `edit_bookings`
* **Issues Found**:
  - **Booking Slip V2 layout discrepancies**: Missing Event Type, guest counts in the wrong section, Rate is not at the bottom left, and Email is still listed on the slip.
* **Recommendations**: Resolve layout inconsistencies in `booking-slip-v2.blade.php`.

---

## 7. Financial Ledgers & Accounting
* **Status**: Completed
* **Completion**: 90%
* **Description**: Core double-entry ledger platform supporting customized charts of accounts, journal vouchers, general ledgers, trial balances, opening balances, and cash & bank account control.
* **Database Tables**:
  - `financial_years`: Account periods.
  - `account_types`: Asset, Liability, Equity, Income, Expense definitions.
  - `accounts`: COA ledger accounts.
  - `account_opening_balances`: Beginning balances.
  - `journal_vouchers`: JV header vouchers.
  - `journal_voucher_items`: JV debits and credits details.
  - `cash_bank_accounts`: Linked system financial accounts.
* **Livewire Components**:
  - [FinancialYearManager](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/Finance/FinancialYearManager.php)
  - [ChartOfAccounts](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/Finance/ChartOfAccounts.php)
  - [OpeningBalances](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/Finance/OpeningBalances.php)
  - [JournalVoucherList](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/Finance/JournalVoucherList.php)
  - [JournalVoucherForm](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/Finance/JournalVoucherForm.php)
  - [GeneralLedger](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/Finance/GeneralLedger.php)
  - [TrialBalance](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/Finance/TrialBalance.php)
  - [CashBankManager](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/Finance/CashBankManager.php)
* **Services**:
  - [AccountingService](file:///c:/wamp64/www/MarqueeCMS/app/Services/AccountingService.php) (JV posting rules, Trial balance calculations)
* **Models**:
  - [FinancialYear](file:///c:/wamp64/www/MarqueeCMS/app/Models/FinancialYear.php)
  - [AccountType](file:///c:/wamp64/www/MarqueeCMS/app/Models/AccountType.php)
  - [Account](file:///c:/wamp64/www/MarqueeCMS/app/Models/Account.php)
  - [JournalVoucher](file:///c:/wamp64/www/MarqueeCMS/app/Models/JournalVoucher.php)
  - [JournalVoucherItem](file:///c:/wamp64/www/MarqueeCMS/app/Models/JournalVoucherItem.php)
* **Routes**: `/finance/chart-of-accounts`, `/finance/journal-vouchers`, `/finance/general-ledger`, `/finance/trial-balance`
* **Permissions**: `manage_accounting` required.
* **Issues Found**: Balance Sheet and Profit & Loss reports are missing.
* **Recommendations**: Add financial reporting templates for Profit & Loss Statements and Balance Sheets.

---

## 8. Inventory & Purchases
* **Status**: Completed
* **Completion**: 95%
* **Description**: Raw inventory tracking for catering materials, purchasing flows (Purchase Orders, Goods Receiving, Purchase Invoices, Purchase Returns), and supplier ledgers.
* **Database Tables**:
  - `inventory_items`: Item stock logs, costs, bounds.
  - `suppliers`, `supplier_ledgers`: Supplier directories.
  - `purchase_orders`, `goods_receiving_notes`, `purchase_invoices`, `purchase_returns` (along with their `_details` tables).
* **Livewire Components**:
  - [ItemList](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/Inventory/ItemList.php)
  - [StockView](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/Inventory/StockView.php)
  - [SupplierList](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/Inventory/SupplierList.php)
  - [PurchaseOrderForm](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/Purchases/PurchaseOrderForm.php)
  - [GoodsReceivingForm](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/Purchases/GoodsReceivingForm.php)
  - [PurchaseInvoiceForm](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/Purchases/PurchaseInvoiceForm.php)
  - [PurchaseReturnForm](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/Purchases/PurchaseReturnForm.php)
* **Models**:
  - [InventoryItem](file:///c:/wamp64/www/MarqueeCMS/app/Models/InventoryItem.php)
  - [Supplier](file:///c:/wamp64/www/MarqueeCMS/app/Models/Supplier.php)
  - [PurchaseInvoice](file:///c:/wamp64/www/MarqueeCMS/app/Models/PurchaseInvoice.php)
* **Services**:
  - [PurchaseService](file:///c:/wamp64/www/MarqueeCMS/app/Services/PurchaseService.php)
* **Permissions**: `view_inventory` required.
* **Issues Found**: None.
* **Recommendations**: Enforce automated double-entry ledger postings on invoice validation (currently posted immediately).

---

## 9. Expense & Petty Cash Management
* **Status**: Completed
* **Completion**: 95%
* **Description**: Controls operating expenses, utility bill tracking, petty cash ledgers, recurring expenses, and expense approvals.
* **Database Tables**:
  - `expenses`, `expense_items`: Expense records.
  - `petty_cash_accounts`, `petty_cash_reconciliations`: Account registers.
  - `recurring_expenses`: Autogenerated schedules.
  - `expense_budgets`: Financial budget restrictions.
* **Livewire Components**:
  - [ExpenseForm](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/Finance/ExpenseForm.php)
  - [PettyCashManager](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/Finance/PettyCashManager.php)
  - [BudgetTracker](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/Finance/BudgetTracker.php)
* **Models**:
  - [Expense](file:///c:/wamp64/www/MarqueeCMS/app/Models/Expense.php)
  - [PettyCashAccount](file:///c:/wamp64/www/MarqueeCMS/app/Models/PettyCashAccount.php)
* **Services**:
  - [ExpenseService](file:///c:/wamp64/www/MarqueeCMS/app/Services/ExpenseService.php)
* **Permissions**: Access is restricted to Managers or Administrators.
* **Issues Found**: None.
* **Recommendations**: Integrate expense statistics directly into the dashboard.
