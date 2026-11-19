# GEMINI.md - MarqueeCMS Technical Architecture & Operational Knowledge Base

> **High-Density System Manual for AI Coding Agents and Developers**  
> **Workspace Root:** `c:\wamp64\www\marquee-cms`  
> **Primary Technology Stack:** PHP 8.2/8.3, Laravel 12.x, Livewire 3/4, Alpine.js, Bootstrap 5.3 + Falcon Admin Template, Tailwind CSS 4, MySQL 8.0+ (Local/Prod), SQLite (In-Memory Testing).

---

## 1. System Overview & Technology Stack

**MarqueeCMS** is an enterprise-grade multi-tenant Software-as-a-Service (SaaS) ERP designed for wedding halls, banquet marquees, catering operations, and event complexes. It integrates front-desk booking reservations, double-entry financial accounting, customer/vendor ledgers, two-stage payment verification, multi-department inventory, recipe costing, staff attendance, payroll, and Pakistani fiscal POS integrations (FBR / PRA).

### Tech Stack Details
- **Backend Framework:** Laravel 12.x (PHP 8.2 / 8.3 strict types).
- **Frontend / Reactivity:** Livewire 3/4 (`#[On]`, `#[Url]`, `wire:model.live`), Alpine.js, Blade components.
- **UI Frameworks:** Bootstrap 5.3 customized with Falcon Admin Template; Tailwind CSS 4 utilities.
- **Reporting & Printing:** DomPDF (`barryvdh/laravel-dompdf`), custom Falcon Print Studio views (A4, Letter, Legal thermal & standard slips).
- **Databases:** MySQL 8.0+ for development and production; SQLite `:memory:` for automated testing.
- **External Integrations:** Stripe Billing SDK (SaaS subscriptions), PRAL/FBR POS API, PRA POS Component API.

---

## 2. Multi-Tenancy & Data Scoping Architecture

### Tenant Hierarchy
```text
Platform (SuperAdmin / SaaS Host)
  \-- Marquee (Tenant Entity / Business Account - `marquee_id`)
        |-- Subscription & Currency Settings
        |-- Chart of Accounts (COA) Definition
        |-- Departments (Kitchen, Store, Housekeeping, Accounts, IT)
        \-- Branch (Facility / Physical Venue - `branch_id`)
              |-- Halls (`hall_id`) & Slots (Morning, Evening, Full Day)
              |-- Branch POS ID / FBR Configuration
              \-- Bookings, Expenses, Inventory, Staff
```

### Global Scoping Rules & Traits
1. **Tenant Isolation:** Tenant models utilize the `BelongsToTenant` trait which applies a global query scope (`TenantScope`) filtering by `marquee_id = auth()->user()->marquee_id`.
2. **Branch Isolation:** Operational models (Bookings, Stock, Petty Cash, Hall Slots) utilize `BelongsToBranch` to scope records by the active branch selected in user session context (`active_branch_id`).
3. **Bypassing Global Scopes:**
   - Always use `withoutGlobalScope('tenant')` or `withoutGlobalScopes()` when resolving system-wide master records, executing SuperAdmin cross-tenant migrations, or seeding baseline Chart of Accounts:
     ```php
     Account::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->first();
     ```
4. **Tenant Switching & Session:** User's active branch is maintained via `session('current_branch_id')` and accessed via helper `$user->getActiveBranchId()` or `$user->getActiveMarqueeId()`.

---

## 3. Role-Based Access Control (RBAC) & User Personas

| Role Name | Scope | Key Capabilities |
| :--- | :--- | :--- |
| **SuperAdmin** | Global Platform | SaaS subscriptions, tenant onboarding, DB migrations, system backups, demo data generation. |
| **Owner / Admin** | Marquee (Tenant) | Full access across all tenant branches, financial year closure, COA management, payroll approval. |
| **Manager** | Branch | Booking creation/adjustments, slot reservation, receipt recording (`pending_posting`), staff attendance. |
| **Accountant** | Marquee / Branch | Two-stage payment posting, voucher approval (CPV/BPV), JV creation, Bank/Cash reconciliations, P&L. |
| **Receptionist / Cashier**| Branch | Booking inquiries, tentative holds, customer payment intake (creates `pending_posting` receipts). |
| **Kitchen / Storekeeper** | Department / Store| Raw ingredient requests, GRN creation, recipe consumption, dish production logging. |

---

## 4. Core Financial & Accounting Engine

MarqueeCMS implements strict double-entry accrual accounting.

### 4.1 Standard Chart of Accounts (Pakistani 4-Digit Standard)
| Code Range | Category | Key Accounts | Nature | Normal Balance |
| :--- | :--- | :--- | :--- | :--- |
| `1000 - 1999` | **Current & Non-Current Assets** | `1001` Cash in Hand<br>`1002` Bank Accounts (Meezan, HBL, etc.)<br>`1003` Accounts Receivable (Customers)<br>`1004` Inventory / Raw Materials Stock<br>`1005` Petty Cash Accounts | Asset | **Debit** |
| `2000 - 2999` | **Current & Non-Current Liabilities** | `2001` Accounts Payable (Vendors / Suppliers)<br>`2002` Customer Security Deposits (Refundable)<br>`2003` Customer Advances / Contract Liabilities<br>`2004` Salaries / Payroll Payable | Liability | **Credit** |
| `3000 - 3999` | **Equity & Retained Earnings** | `3001` Capital Account<br>`3002` Retained Earnings | Equity | **Credit** |
| `4000 - 4999` | **Operating & Service Revenue** | `4001` Event Hall Rental Income<br>`4002` Food & Catering Revenue<br>`4003` Vendor Commission Revenue<br>`4004` Extra Services Income | Revenue | **Credit** |
| `5000 - 5999` | **Cost of Goods Sold (COGS)** | `5001` Kitchen Raw Material Consumption<br>`5002` Event Direct Labor Cost | Expense | **Debit** |
| `6000 - 6999` | **Operating / Administrative Expenses** | `6001` Staff Salaries Expense<br>`6002` Electricity & Utilities<br>`6003` Maintenance & Repairs<br>`6004` Marketing & Advertising | Expense | **Debit** |

### 4.2 Double-Entry Invariants
- Every `JournalVoucher` has two or more `JournalVoucherItem` rows.
- Strictly validated in [AccountingService.php](file:///c:/wamp64/www/marquee-cms/app/Services/AccountingService.php):
  $$\sum \text{Debits} == \sum \text{Credits}$$
- Vouchers must belong to an `active` `FinancialYear`. Posting outside active financial dates is blocked.

### 4.3 Two-Stage Customer Payment Workflow
```text
[Front-Desk / Manager Entry]
         |
         v
`recordPayment()` 
|-- Creates `BookingPayment` in `pending_posting` status
|-- Generates sequential payment number (`PAY-2026-00001`)
|-- Does NOT create Journal Voucher
\-- Does NOT alter Cash-in-Hand or Bank balance
         |
         v
[Accountant Review & Posting in Payment Ledger]
         |
         v
`postPayment()`
|-- Updates `BookingPayment->status = 'posted'`
|-- Generates balanced `JournalVoucher` (`JV-2026-BR-00001`):
|     * Debit: Cash Account (`1001`) or Bank Account (`1002`)
|     * Credit: 
|         - If `payment_type == 'security_deposit'`: Security Deposits Liability (`2002`)
|         - If revenue not recognized: Customer Advance Liability (`2003`)
|         - If revenue is recognized: Accounts Receivable (`1003`)
|-- Updates `CustomerLedger` running balance
|-- Calls `recalculateBookingFinancials($booking)`
\-- If linked to `vendor_sale_id`, calls `recalculateVendorSaleCustomerFinancials()`
```

### 4.4 Payment Rejection & Reversal Lifecycle
1. **Rejection (`rejectPayment`):** Used when payment is still `pending_posting`. Status changed to `rejected`. No journal entries existed to reverse. Recalculates booking and vendor sale financials.
2. **Reversal (`reversePayment`):** Used when a `posted` payment must be cancelled. Accountant specifies reason. Status changed to `reversed`. Voids/reverses associated `JournalVoucher`, creates debit/credit adjustment in `CustomerLedger`, and triggers `recalculateBookingFinancials()` and `recalculateVendorSaleCustomerFinancials()`.

---

## 5. Booking Engine, Pricing & Final Bill Mathematics

### 5.1 Booking Lifecycle States
$$\text{inquiry} \longrightarrow \text{tentative} \longrightarrow \text{confirmed} \longrightarrow \text{in\_progress} \longrightarrow \text{completed} \quad (\text{or } \text{cancelled})$$
- **Availability Check:** [AvailabilityService.php](file:///c:/wamp64/www/marquee-cms/app/Services/AvailabilityService.php) ensures no slot conflicts on `(hall_id, event_date, slot)` where slots are `morning`, `evening`, or `full_day`.
- **Guest Confirmation:** A booking cannot transition to `confirmed` without specified minimum guaranteed guest count.

### 5.2 Canonical Financial & Final Bill Formulas
Defined in [BookingFinancialService.php](file:///c:/wamp64/www/marquee-cms/app/Services/BookingFinancialService.php) and [BookingPricingService.php](file:///c:/wamp64/www/marquee-cms/app/Services/BookingPricingService.php):

1. **Package Catering Amount:**
   $$\text{package\_amount} = \begin{cases} 0.00 & \text{if } \text{no\_food} = \text{true} \\ \text{guest\_count} \times \text{per\_plate\_price} & \text{otherwise} \end{cases}$$

2. **Event Operational Subtotal:**
   $$\text{subtotal} = \text{package\_amount} + \text{hall\_charges} + \text{extra\_charges}$$

3. **Tax Calculation (FBR / PRA / Regional Sales Tax):**
   - Tax is applied **strictly** to event catering, hall rental, and marquee extra services.
   - **Crucial Rule:** Service provider / vendor charges and security deposits are **strictly non-taxable**.
   $$\text{tax\_amount} = \operatorname{round}\left(\frac{\text{subtotal} \times \text{tax\_rate}}{100}, 2\right)$$

4. **Event Grand Total (Discount Calculated in Last):**
   - **Crucial Business Rule (Commit `6d02924`):** When preparing and adjusting the final bill, discount amount is deducted at the very end from total charges plus tax and deposit:
   $$\text{grand\_total} = \max\left(0.00, \text{subtotal} + \text{tax\_amount} + \text{security\_deposit} - \text{discount\_amount}\right)$$

5. **Effective Invoice Amount & Customer Balance:**
   $$\text{effective\_invoice\_amount} = \text{grand\_total} + \text{total\_vendor\_charges}$$
   $$\text{balance\_due} = \text{effective\_invoice\_amount} - \text{total\_paid\_posted}$$

---

## 6. Vendor & Service Provider Partnerships

Managed via [VendorCommissionService.php](file:///c:/wamp64/www/marquee-cms/app/Services/VendorCommissionService.php) and models `Vendor`, `VendorService`, `VendorSale`, `VendorLedger`.

### 6.1 Settlement Modes
1. **Invoiced Mode (`include_in_invoice = true`):**
   - Marquee invoices the customer for the vendor service.
   - Marquee collects payment from the customer.
   - Commission is retained by Marquee (`revenue = commission_amount`).
   - Marquee pays net to vendor:
     $$\text{vendor\_payable} = \text{total\_vendor\_amount} - \text{commission\_amount}$$
2. **Direct Pay Mode (`include_in_invoice = false`):**
   - Customer pays the vendor directly outside Marquee.
   - Vendor owes commission to Marquee:
     $$\text{vendor\_receivable} = \text{commission\_amount}$$

### 6.2 Customer Advance Synchronization
When customer advance payments are collected for a specific vendor service (`vendor_sale_id`):
- `BookingPayment` records reference `vendor_sale_id`.
- If an advance payment is rejected or reversed in the accounting ledger, `recalculateVendorSaleCustomerFinancials(VendorSale $sale)` ensures that:
  - `advance_collected` reflects only `posted` or active pending payments.
  - Remaining vendor balance due is updated accurately without orphan liabilities.

---

## 7. Inventory, Multi-UOM, Kitchen & Departments

### 7.1 Architecture: Central Warehouse vs Department Stock
- **Central Warehouse:** Managed at branch level via `InventoryStockLedger`. Receives stock via Goods Receiving Notes (`GoodsReceivingNote`).
- **Departments:** Kitchen, Housekeeping, IT, Maintenance, Decor. Each department maintains an isolated stock balance tracked via `DepartmentStockLedger`.

### 7.2 Multi-Tier Unit of Measure (UOM)
- Managed by [UomConversionService.php](file:///c:/wamp64/www/marquee-cms/app/Services/UomConversionService.php).
- Supports base units (e.g. `Kg`, `Liter`, `Piece`) and purchase/recipe units (e.g. `Carton`, `Bag`, `Gram`, `Milliliter`).
- Factor-based conversions handle fractional quantities with precision rounding.

### 7.3 Department Requisition & Issue Workflow
```text
Department Creates Request (`DepartmentStockRequest`)
  |
  v
Store Manager Approves & Dispatches (`DepartmentStockIssue`)
  |-- Deducts stock from Central Warehouse (`InventoryStockLedger` qty_out)
  |-- Adds stock to Department Warehouse (`DepartmentStockLedger` qty_in)
  \-- Auto-creates COGS or Inter-Department Transfer Journal Voucher
```

### 7.4 Kitchen Recipe & Dish Production
- `Recipe` & `RecipeDetail` define ingredient requirements per guest plate.
- [RecipeService.php](file:///c:/wamp64/www/marquee-cms/app/Services/RecipeService.php) calculates aggregate ingredient requirements:
  $$\text{required\_qty} = \text{quantity\_per\_head} \times \text{guest\_count}$$
- `DepartmentProduction`: Logs actual cooked dishes, deducting raw materials from Kitchen stock and posting consumption expense (`5001`).

---

## 8. HR, Staff Attendance & Payroll Engine

### 8.1 Employee Management
- Model: `Employee`, `Department`, `Designation`, `Shift`.
- Attributes: Basic salary, allowance package, CNIC, employment type (monthly, daily-wage, event-basis), shift timings, grace period.

### 8.2 Daily Attendance & Shift Rostering
- Attendance statuses: `present`, `absent`, `late`, `half_day`, `on_leave`.
- Automatic late calculation:
  - If `check_in > shift_start_time + grace_period_minutes`, mark `late`.
  - Configurable policy: $X$ late days equal 1 half-day or 1 unpaid leave deduction.

### 8.3 Monthly Payroll Computation & Disbursement
$$\text{gross\_salary} = \text{basic\_salary} + \text{allowances} + \text{overtime\_pay}$$
$$\text{total\_deductions} = \text{unpaid\_leaves} + \text{late\_penalties} + \text{salary\_advances} + \text{tax}$$
$$\text{net\_payable} = \text{gross\_salary} - \text{total\_deductions}$$

- **Disbursement GL Entries:**
  - **Debit:** Staff Salaries Expense (`6001`)
  - **Credit:** Salary Advances (`1005` or advance asset account)
  - **Credit:** Salaries Payable (`2004`)
  - When paid via Cash/Bank: Debit `2004` (Salaries Payable), Credit `1001`/`1002` (Cash/Bank).

---

## 9. Tax Authorities & Fiscal POS Integration

Implemented in [FbrPosService.php](file:///c:/wamp64/www/marquee-cms/app/Services/FbrPosService.php) and [PraPosService.php](file:///c:/wamp64/www/marquee-cms/app/Services/PraPosService.php):

### Regional Tax Authorities
- **FBR (Federal):** Islamabad Capital Territory.
- **PRA (Punjab):** Punjab Revenue Authority (Lahore, Rawalpindi, Faisalabad, etc.). Standard 13% services tax or 5% reduced rate.
- **SRB (Sindh):** Sindh Revenue Board.
- **KPRA (KPK):** Khyber Pakhtunkhwa Revenue Authority.
- **BRA (Balochistan):** Balochistan Revenue Authority.

### Technical Implementation
- **Cloud vs Local POS:** Supports Cloud API sync and Local PRAL Windows Middleware (`localhost:8524`).
- **Invoice Signing & QR Code:** Invoices generate 16-character FBR invoice numbers and embed base64/SVG QR codes conforming to authority schema.
- **Offline Resiliency:** When internet is disconnected, bills are marked `offline_queued` and synced via scheduled background jobs.

---

## 10. Key Directory Map & Component Index

```text
c:\wamp64\www\marquee-cms
|-- app/
|   |-- Models/                        # 105+ Eloquent models
|   |   |-- Booking.php                # Core reservation entity
|   |   |-- BookingPayment.php         # Customer payment records
|   |   |-- BookingFinalBill.php       # Event-day settlement bill
|   |   |-- Account.php                # Chart of accounts
|   |   |-- JournalVoucher.php         # Double-entry ledger vouchers
|   |   |-- CustomerLedger.php         # Customer balance tracking
|   |   |-- VendorSale.php             # Vendor partnership sales
|   |   |-- InventoryStockLedger.php   # Central warehouse ledger
|   |   |-- DepartmentStockLedger.php  # Department-specific inventory
|   |   |-- Employee.php               # HR staff entity
|   |   \-- Payroll.php                # Monthly payroll processing
|   |-- Services/                      # 20 Business logic engines
|   |   |-- AccountingService.php      # Double-entry JV creation & balancing
|   |   |-- BookingFinancialService.php# Payment recording, posting, recalculation
|   |   |-- BookingPricingService.php  # Subtotal, tax, grand total calculation
|   |   |-- AvailabilityService.php    # Hall/slot conflict prevention
|   |   |-- VendorCommissionService.php# Commission agreements & payouts
|   |   |-- DepartmentStockService.php # Requisitions, issues & department stock
|   |   |-- InventoryService.php       # Stock tracking & code generators
|   |   |-- RecipeService.php          # Per-head ingredient demand
|   |   |-- UomConversionService.php   # Unit conversion factors
|   |   |-- FbrPosService.php          # Fiscal POS synchronization
|   |   \-- SyntheticDataGeneratorService.php # Demo data seeding
|   \-- Livewire/                      # Reactive components
|       |-- BookingView.php            # Primary booking management interface
|       |-- Finance/                   # General ledger, vouchers, P&L, balance sheet
|       |-- Inventory/                 # Stock takes, transfers, inventory dashboard
|       |-- Purchases/                 # POs, GRNs, supplier bills & ledgers
|       |-- Payroll/                   # Attendance, salary slips, advances
|       \-- Owner/                     # Multi-branch analytics, cash-in-hand modals
|-- resources/views/
|   |-- livewire/                      # Livewire Blade UI templates
|   |-- falcon-print/                  # High-density printable slips & invoices
|   \-- bookings/                      # PDF contract & receipt templates
|-- tests/
|   |-- Feature/                       # 70+ End-to-end integration test suites
|   \-- Unit/                          # Mathematical & unit test suites
\-- routes/
    |-- web.php                        # Authenticated app routes & Livewire endpoints
    \-- console.php                    # Scheduled artisan tasks (attendance, backups)
```

---

## 11. Developer CLI & Verification Commands

### Running Automated Test Suites
Run tests cleanly with Xdebug disabled:
```bash
# Run specific feature test
php "-dxdebug.mode=off" vendor/phpunit/phpunit/phpunit tests/Feature/PrepareFinalBillTaxTest.php

# Run full financial suite
php "-dxdebug.mode=off" vendor/phpunit/phpunit/phpunit --filter=BookingFinancialAccountingTest

# Run all tests
php "-dxdebug.mode=off" vendor/phpunit/phpunit/phpunit
```

### Seeding Demo & Master Data
```bash
# Provision comprehensive synthetic multi-branch demo data
php artisan app:generate-synthetic-data

# Fresh database migration with seeders
php artisan migrate:fresh --seed
```

### Clearing Application Caches
```bash
php artisan optimize:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
```

---

## 12. Crucial Coding Rules for AI Agents

1. **Always Lock on Financial Mutations:** When modifying `Booking`, `BookingPayment`, `CustomerLedger`, or `VendorSale`, wrap transactions in `DB::transaction()` and use `->lockForUpdate()`.
2. **Never Post Payments Directly in Front-Desk Actions:** Managers and receptionists only create `pending_posting` payments. Posting is restricted to accountants and owners.
3. **Always Trigger Financial Recalculation:**
   - Any payment creation, posting, rejection, or reversal must call:
     ```php
     app(BookingFinancialService::class)->recalculateBookingFinancials($booking);
     ```
   - If payment contains `vendor_sale_id`, also call:
     ```php
     app(VendorCommissionService::class)->recalculateVendorSaleCustomerFinancials($vendorSale);
     ```
4. **Final Bill Discount Rule:** Discount must always be deducted **in last** (after calculating subtotal, applying tax to non-vendor event charges, and adding security deposit).
5. **Non-Taxable Items:** Vendor services (`BookingVendorService`) and security deposits (`security_deposit`) must never have tax applied to them.
6. **Double-Entry Balance Enforcement:** Every debit entry must be counter-balanced by an identical credit entry before saving a `JournalVoucher`. Never allow orphaned voucher items.
