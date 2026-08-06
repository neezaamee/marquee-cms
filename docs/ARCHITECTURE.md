# Architecture, Security, Performance & UI/UX Audit — MarqueeCMS

This document reviews the codebase of MarqueeCMS against enterprise software architecture standards, Laravel best practices, and security guidelines.

---

## 1. Architectural Integrity (SOLID & Design Patterns)

MarqueeCMS adopts a hybrid design pattern, implementing a Service Layer for major modules, but displaying inconsistencies in repository pattern usage.

### 1.1 Service Layer Architecture
The application correctly delegates complex operational logic to service classes within the `app/Services/` directory:
* **`AccountingService`**: Manages voucher validation, general ledger processing, and trial balance reports.
* **`AvailabilityService`**: Core booking slot conflict check engine.
* **`PurchaseService`**: Handles PO, GRN, and Purchase Invoice lifecycle.
* **`ExpenseService`**: Operates petty cash records and budget limits.

These service classes isolate business rules from controllers and Livewire components, ensuring single responsibility.

### 1.2 Repository Pattern Inconsistency
* **Observation**: The Repository Pattern is implemented **only** for the Expense module (via `ExpenseRepository` and `ExpenseRepositoryInterface`). All other modules (Bookings, Customers, Accounting, Inventory) interact directly with Eloquent models inside Services or Livewire code.
* **Technical Debt**: This hybrid approach creates architectural confusion for developer onboarding. 
* **Recommendation**: Either expand the repository pattern across all entities or phase out the `ExpenseRepository` to interact with Eloquent directly through the `ExpenseService` for consistency.

---

## 2. Security Audit

### 2.1 Multi-Tenant & Branch Isolation Security
* **Strength**: The `BelongsToTenant` and `BelongsToBranch` traits automatically register global scopes that restrict data query results to the logged-in user's marquee and branch.
* **Vulnerability**: Direct usage of raw DB facades (e.g. `DB::table('bookings')->get()`) bypasses Eloquent global scopes. In `AccountingService.php` (line 252) and `AvailabilityService.php` (line 109), direct `DB::table(...)` queries are run. While currently safe because they query non-tenant tables or restrict fields, developers must be strictly cautioned against using direct query builder calls for tenant models.
* **Recommendation**: Implement static review rules to prevent raw DB queries on tenant-scoped tables.

### 2.2 Access Control Authorization Mismatch
* **Vulnerability**: The project is advertised as using the Spatie Laravel Permission package, but actually uses a custom-built pivot relation (`permission_role`). 
* **Risk**: Custom authorization checks (e.g. `$this->role->permissions()->where('name', $permissionName)->exists()`) query the database on every permission check. Unlike Spatie Permission, this does not have automated caching, leading to redundant DB query hits.
* **Recommendation**: Migrate the custom RBAC system to Spatie Laravel Permission or add query caching on permission validation checks.

---

## 3. Performance Review

### 3.1 N+1 Query Traps in Customer List
* **Vulnerability**: The Customer list component ([CustomerList](file:///c:/wamp64/www/MarqueeCMS/app/Livewire/CustomerList.php)) paginates customers and renders a table. For each customer row, accessors like `$customer->total_bookings` and `$customer->outstanding_balance` are queried dynamically:
  - This executes a separate sub-query count per customer row (e.g. `SELECT count(*) FROM bookings WHERE customer_id = ...`).
  - Loading a page of 10 customers triggers 30+ database queries to render the table.
* **Recommendation**: Eager-load aggregate fields during customer pagination:
  ```php
  $customers = Customer::withCount('bookings')
      ->withSum(['bookings as total_revenue' => function($query) {
          $query->whereNotIn('booking_status', ['Cancelled', 'Rejected']);
      }], 'grand_total')
      ->latest()
      ->paginate(10);
  ```

---

## 4. UI/UX Review & Falcon Template Integration

### 4.1 Static Dashboard Mockups
* **Observation**: The main application dashboard ([dashboard.blade.php](file:///c:/wamp64/www/MarqueeCMS/resources/views/dashboard.blade.php)) features hardcoded stats (142 bookings, $45.2K Revenue) and static tables.
* **UX Impact**: Creates a poor initial user experience. Dashboard figures must load live statistics.
* **Recommendation**: Bind dashboard metrics to real DB counts in a `DashboardController` or Livewire component.

### 4.2 Sidebar Dead Links
* **Observation**: Multiple sidebar menu links (e.g. Analytics, Quick Stats, Upcoming Events, VIP Customers) feature a class `text-muted` and `href="#!"` with a "Soon" pill.
* **Recommendation**: Hide or disable items marked "Soon" until they are fully functional, providing a cleaner admin interface.
