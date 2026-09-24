# SECURITY_BASELINE_REPORT.md

**Project:** Marquee / Marriage Hall Management SaaS CMS  
**Date:** September 24, 2026  
**Phase:** Phase 0 — Baseline Security Analysis  
**Auditor:** Antigravity AI Security Review  
**Repository:** `marquee-cms`  
**Platform / Stack:** Laravel 13.x | PHP 8.3 | MySQL 8.x | Livewire 3 | Bootstrap 5 | Falcon Admin  

---

## 1. Executive Baseline Overview

In accordance with Phase 0 Master Rules, a comprehensive non-destructive security analysis of the entire existing codebase was conducted. No production code was modified during this phase.

The application possesses a sophisticated multi-tenant and branch-aware foundation using Laravel Eloquent global scopes (`BelongsToTenant`, `BelongsToBranch`), subscription verification middleware (`EnsureSubscriptionIsActive`), and an onboarding gate (`EnsureInitialSetupIsCompleted`). However, concrete gaps and high-risk vulnerabilities exist across:
1. **Public Information Disclosure & DoS:** Route `/debug-cache` clears cache and exposes DB credentials publicly.
2. **Path Traversal Risk:** Route `storage/{path}` lacks path containment checks (`realpath`).
3. **Sensitive Credential Leakage in Audit Trail:** `LogsActivity` persists bcrypt password hashes and remember tokens directly into `activity_logs`.
4. **Cross-Tenant IDOR in Audit Viewer:** `ActivityLogManager::showDetailModal()` strips global tenant scopes without tenant authorization checks.
5. **Absence of Authentication Rate Limiting:** `LoginController::login()` and `RegisterController::register()` lack rate limiting.
6. **Account & Password Enumeration:** Differing validation responses leak whether credentials are valid for deactivated users.
7. **Deactivated Staff Session Persistence:** No post-login middleware verifies user status; deactivated staff retain active sessions until expiration.
8. **Livewire Action-Level Authorization Gaps:** Action methods in admin components rely solely on initial `mount()` authorization.
9. **Multi-Tenant Inconsistencies for Business Owners:** In `CustomerController`, `UserForm`, and `CustomerForm`, checking `$user->marquee_id` instead of `$user->getActiveMarqueeId()` or `$user->hasAccessToMarquee()` breaks access for Business Owners whose `users.marquee_id` is `null`.
10. **Dead Password Recovery Flow:** "Forgot Password?" in the login UI is a dead anchor `#` with no backend broker or route.

---

## 2. In-Depth Baseline Security Analysis by Domain

### A. Authentication
- **Login Flow (`App\Http\Controllers\Auth\LoginController`):**
  - **Inputs:** Accepts `login` (email or username) and `password`. Lacks maximum string length rules (`max:255`) and whitespace trimming.
  - **Resolution:** Resolves user via `\App\Models\User::withoutGlobalScope('tenant')->where($field, $loginInput)->first()`.
  - **Status & Password Verification:** If user exists, evaluates `Hash::check()`. If password matches but `strtolower($user->status) !== 'active'`, it throws `"Your account has been deactivated. Please contact your administrator."`. This leaks account existence and password correctness.
  - **Session Regeneration:** Calls `$request->session()->regenerate()` upon successful `Auth::attempt()`.
  - **Remember Me:** Supported via `$request->boolean('remember')` passed to `Auth::attempt()`.
- **Logout Flow (`LoginController::logout`):**
  - Logs `logout` action to `ActivityLog`.
  - Calls `Auth::logout()`, `$request->session()->invalidate()`, and `$request->session()->regenerateToken()`. Implementation is sound.
- **Registration Flow (`App\Http\Controllers\Auth\RegisterController`):**
  - Public registration creates user with `name`, `email`, and `password`.
  - **Gaps:** Does not assign default role (`role_id` is null); does not assign trial/subscription; logs user in via `Auth::login($user)` **without** session regeneration (session fixation risk); has zero rate limiting.
- **Password Reset:**
  - `login.blade.php` renders `<a class="fs-10" href="#">Forgot Password?</a>`.
  - The migration `0001_01_01_000000_create_users_table.php` provisions `password_reset_tokens`, but no routes, controllers, or notifications exist.
- **Password Change (`App\Http\Controllers\ProfileController::updatePassword`):**
  - Requires `current_password` and confirms `password` against `Rules\Password::defaults()`.
  - **Gaps:** Does not call `Auth::logoutOtherDevices()`; does not log password change event to `ActivityLog`; does not invalidate remember tokens.

### B. Authorization (RBAC & Action-Level Guarding)
- **RBAC Schema:** `roles` and `permissions` connected via `permission_role`.
- **Role Scoping:** Roles and permissions are system-wide global templates (no `marquee_id` column). Any permission toggle in `admin/access-control` alters permissions globally for all tenants.
- **Model Methods:**
  - `User::hasRole()`, `User::hasPermission()`, `User::isSuperAdmin()`, `User::isBusinessOwner()`.
  - `hasPermission()` automatically returns `true` for Super Admin and Business Owner.
- **Controller Authorization:**
  - High inconsistency: Controllers rely on manual `abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission(...), 403)`.
  - `StaffController`: Methods `index`, `create`, `store`, `show`, `edit`, `update`, and `destroy` contain **zero permission checks**. Any authenticated user can browse, create, modify, or delete staff records.
- **Livewire Action Authorization:**
  - Livewire 3 components execute `mount()` only on initial GET render. Subsequent calls to `/livewire/update` do not rerun `mount()`.
  - In `SuperAdmin\BackupManager`, `executeRestore()` has no authorization check inside the method.
  - In `SaasPaymentForm`, `savePayment()` lacks an action-level check for Super Admin.
  - In `BookingWizard`, `submitBooking()` verifies branch access but does not verify `create_bookings` permission.

### C. Tenant Isolation
- **Tenant Scope (`App\Traits\BelongsToTenant`):**
  - Automatically sets `marquee_id = $activeId` upon model `creating` event if `!$model->marquee_id`.
  - Applies global query scope `tenant`: `$builder->where($table . '.marquee_id', $activeMarqueeId)`.
  - Super Admin is exempt.
- **Business Owner Disconnect (`users.marquee_id = null`):**
  - Business owners own marquees via the `marquee_owners` pivot table. Their primary `users.marquee_id` column is often `null`.
  - In `CustomerController` (lines 36 & 51) and `UserForm` (line 68), authorization checks compare `$model->marquee_id !== auth()->user()->marquee_id`. Because `auth()->user()->marquee_id` is null, legitimate Business Owners are blocked from their own records with 403 errors.
- **Mass Assignment Vulnerability in `BelongsToTenant`:**
  - If a model has `marquee_id` in `$fillable` and a request payload contains `marquee_id`, `!$model->marquee_id` is false. The user-provided `marquee_id` is preserved, allowing potential cross-tenant record injection.
- **Branch Scope (`App\Traits\BelongsToBranch`):**
  - Filters queries by `$user->branch_id` if user has a branch assignment and is neither Super Admin nor Business Owner.
  - In `StaffController::store` and `update`, `branch_id` is validated as `'required|exists:branches,id'` without scoping to the current tenant's marquee.

### D. Security Logging
- **Activity Log (`App\Models\ActivityLog`, `App\Traits\LogsActivity`):**
  - Trait listens to `created`, `updated`, `deleted` model events.
  - Dirty attributes are collected via `$model->getDirty()`.
  - **CRITICAL DEFECT:** Only `['updated_at', 'created_at', 'updated_by']` are skipped. When a `User` is created or updates their password, **bcrypt password hashes and remember tokens are stored in plaintext JSON within `activity_logs.new_values` and `old_values`**.
  - **Failed Logins:** `LoginController::login()` fails to log failed login attempts, blinding administrators to brute-force attacks.
  - **Tenant IDOR in Log Viewer:** `ActivityLogManager::showDetailModal(int $logId)` executes `ActivityLog::withoutGlobalScope('tenant')->find($logId)` with no tenant verification, allowing any tenant owner to read any other tenant's audit trail and inspect password hashes.

### E. Administrative Endpoints
- **Debug Route (`routes/web.php:40-56`):**
  - `Route::get('/debug-cache', ...)` is entirely public and unauthenticated.
  - Runs `Artisan::call('config:clear')` and `Artisan::call('cache:clear')`.
  - Dumps `DB Database`, `DB Username`, and `DB Host` in JSON.
- **Super Admin Routes:**
  - Resource routes `subscription-plans`, `plan-features`, `billing-cycles`, `saas-invoices`, `saas-payments`, and Livewire routes `admin/backups`, `admin/migrations`, `admin/business-owners` are placed inside the generic `auth` middleware group without a dedicated `super_admin` middleware wrapper. Protection relies entirely on manual `abort_unless` calls scattered throughout.

### F. Rate Limiting
- `Route::post('/login')` in `routes/web.php` has no rate-limiting middleware.
- `LoginController::login()` does not use Laravel's `RateLimiter` facade.
- `Route::post('/register')` has no rate-limiting middleware.
- An attacker can execute unlimited password-guessing requests against any user account or spam registration.

### G. Session Invalidation
- **User Deactivation:** When an administrator deactivates a user account, no middleware verifies user status on subsequent requests. The user's active session cookie continues to function until expiration.
- **Password Change:** When a user changes their password in `ProfileController::updatePassword`, existing sessions on other devices are not revoked (`Auth::logoutOtherDevices` is not invoked).

### H. File / Path Security
- **Public Storage Route (`routes/web.php:32-38`):**
  - `Route::get('storage/{path}', function ($path) { $filePath = storage_path('app/public/' . $path); if (!file_exists($filePath)) { abort(404); } return response()->file($filePath); })->where('path', '.*');`
  - Uses raw regex `.*` without validating directory traversal (e.g. `../`). If an attacker requests `storage/../../.env`, it could traverse out of `storage/app/public`.
- **Backup Downloads (`BackupDownloadController`):**
  - Properly verifies `auth()->user()->isSuperAdmin()` and validates file existence.

---

## 3. Comprehensive Baseline Findings Table

| Area | Current Status | Risk | Existing Implementation | Required Action |
|---|---|---|---|---|
| **Debug Endpoint** | Publicly accessible; no authentication | **P0** (Critical) | `routes/web.php:L40-L56` runs `config:clear`, `cache:clear`, and returns DB host/user credentials | Remove `/debug-cache` from `routes/web.php`. |
| **Storage Traversal** | Uncurated wildcard route | **P0** (Critical) | `routes/web.php:L32-L38` serves `storage/{path}` with `where('path', '.*')` without path traversal check | Validate `realpath($filePath)` starts with `storage_path('app/public')`. |
| **Sensitive Data Logging** | Password hashes stored in logs | **P0** (Critical) | `app/Traits/LogsActivity.php:L25-L37` persists dirty `password` and `remember_token` into `activity_logs` JSON | Redact `password`, `remember_token`, and security keys from model attribute tracking. |
| **Activity Log Tenant Isolation** | Cross-tenant IDOR in detail modal | **P0** (Critical) | `app/Livewire/ActivityLogManager.php:L143-L166` calls `withoutGlobalScope('tenant')->find($logId)` with no tenant check | Verify `$log->marquee_id` belongs to user's accessible marquees in `showDetailModal()`. |
| **Authentication Rate Limiting** | Zero rate limiting on login/register | **P0** (Critical) | `LoginController.php:L23-L76` has no throttling mechanism or rate limiter | Implement Laravel `RateLimiter` (IP + user throttle) without permanent account lockout. |
| **Livewire Critical Authorization** | Actions rely solely on `mount()` | **P0** (Critical) | `SaasPaymentForm::savePayment()`, `BackupManager::executeRestore()`, etc. lack action-level checks | Enforce server-side authorization checks inside sensitive Livewire action methods. |
| **Deactivated User Session** | Inactive users retain active sessions | **P0** (Critical) | No middleware verifies user status post-login; only checked at login time | Create `EnsureUserIsActive` middleware and apply to all authenticated routes. |
| **Account Enumeration** | Status check leaks valid credentials | **P1** (High) | `LoginController.php:L39-L49` returns specific deactivation error only if password is valid | Return generic `__('auth.failed')` for all failed credential/status attempts. |
| **Business Owner Tenant Access** | Hardcoded `$user->marquee_id` comparison | **P1** (High) | `CustomerController:L36`, `UserForm:L68` break for owners because `users.marquee_id` is null | Check `$user->hasAccessToMarquee($model->marquee_id)` instead of static column. |
| **Staff Management RBAC** | CRUD methods open to all users | **P1** (High) | `StaffController:L19-L196` lacks permission checks on index, create, store, edit, update, delete | Add `abort_unless(auth()->user()->can('manage_staff'), 403)` to `StaffController` methods. |
| **Branch Cross-Tenant Spoof** | Branch ID validation unscoped | **P1** (High) | `StaffController:L66,L152` validates `exists:branches,id` across the entire database | Scope `branch_id` validation to user's active marquee in `store()` and `update()`. |
| **Password Reset Flow** | Missing / dead placeholder `#` | **P2** (Medium) | `login.blade.php:L65` has `href="#"`; no controller or broker routes exist | Implement standard Laravel password reset workflow with secure signed tokens. |
| **Session Fixation on Register** | Session not regenerated on register | **P2** (Medium) | `RegisterController:L39` calls `Auth::login($user)` without `$request->session()->regenerate()` | Call `$request->session()->regenerate()` before redirecting to dashboard. |
| **Concurrent Session Invalidation** | Password change doesn't revoke sessions | **P2** (Medium) | `ProfileController:L103-L116` updates password without `Auth::logoutOtherDevices()` | Invalidate other active sessions upon successful password change. |
| **Failed Login Auditing** | Failed authentication unrecorded | **P2** (Medium) | `LoginController:L73` throws validation exception without recording `ActivityLog` | Record failed login events (IP, attempted username) in `ActivityLog`. |
| **Password Complexity Consistency** | Form rules permit 6 chars vs 8 | **P2** (Medium) | `ManageStaffLogins:L49`, `UserForm:L127` permit `min:6`, while registration requires `min:8` | Centralize validation to `Rules\Password::defaults()` across all forms. |
| **Security Headers** | Missing HTTP security headers | **P3** (Low) | No middleware injects `X-Frame-Options`, `X-Content-Type-Options`, `CSP` | Implement security header middleware compatible with Livewire and Falcon UI. |

---

## 4. Synthesis & Categorization

### 1. Confirmed Vulnerabilities
1. **P0:** Public route `/debug-cache` leaks database connection credentials and clears cache.
2. **P0:** Public route `storage/{path}` allows path traversal if storage symlink is absent.
3. **P0:** User bcrypt password hashes and remember tokens are stored in plaintext JSON inside `activity_logs`.
4. **P0:** `ActivityLogManager::showDetailModal()` has a cross-tenant IDOR vulnerability, exposing password hashes to other business owners.
5. **P0:** No rate limiting exists on `POST /login` or `POST /register`, allowing automated credential stuffing and brute force.
6. **P0:** Livewire action methods (`savePayment`, `executeRestore`) lack action-level authorization checks.
7. **P0:** Deactivated users retain full system access via existing active session cookies.
8. **P1:** `LoginController::login()` leaks account existence and password validity for deactivated users.
9. **P1:** Multi-tenant access breaks for Business Owners in `CustomerController` and `UserForm` due to checking `null` `user->marquee_id`.
10. **P1:** `StaffController` allows any authenticated user to create, edit, or delete staff records without permission checks.

### 2. Already-Fixed Findings
- **CSRF Protection:** Fully active and enforced via Laravel web middleware group across all web forms and Livewire endpoints.
- **Session Serialization:** Already hardened to `json` in `config/session.php` (no PHP object deserialization gadget risks).
- **HTTPS Enforcement in Production:** Configured in `AppServiceProvider::boot()` via `URL::forceScheme('https')`.
- **Tenant Scope on Primary Queries:** Most domain models (`Booking`, `Customer`, `Hall`, `Branch`) have `BelongsToTenant` and `BelongsToBranch` active.

### 3. False Positives
- **Global Roles Table:** `roles` and `permissions` tables do not have `marquee_id`. This was verified as an **intended architectural design decision** (system-wide standard roles across the SaaS platform), not an accidental tenant leakage. However, modifying permissions should remain restricted exclusively to Super Admins.
- **DomPDF Streaming:** Direct PDF generation routes in `routes/web.php` for invoices and slips were investigated for file write vulnerabilities; they use in-memory streaming (`$pdf->stream(...)`) rather than unauthenticated file writes.

### 4. Areas Requiring Additional Investigation
- **Livewire Components with Sensitive File Uploads:** Ensure `photo` and receipt uploads in `CustomerForm`, `BranchForm`, and `StaffController` strictly validate MIME types, extensions, and file sizes.
- **Session Cookie Flagging in Reverse Proxies:** Verify `SESSION_SECURE_COOKIE` behavior behind reverse proxies where TLS terminates at the load balancer.

---

## 5. Recommended Implementation Sequence

```
Phase 0: Baseline Security Analysis (COMPLETED)
   │
   ▼
Phase 1: Critical Security Remediation (P0)
   ├── 1. Remove /debug-cache & secure storage/{path} path containment
   ├── 2. Redact sensitive attributes (password, remember_token) from LogsActivity
   ├── 3. Enforce tenant isolation in ActivityLogManager::showDetailModal
   ├── 4. Implement safe login RateLimiter (IP + identity throttling)
   ├── 5. Add server-side action guards to administrative Livewire methods
   └── 6. Implement EnsureUserIsActive middleware for session termination
   │
   ▼
Phase 2: Authentication Hardening (P1 / P2)
   ├── 1. Standardize generic auth error to prevent account enumeration
   ├── 2. Implement secure Password Reset broker workflow
   ├── 3. Fix session fixation on registration ($request->session()->regenerate())
   ├── 4. Invalidate concurrent sessions on password change (logoutOtherDevices)
   ├── 5. Record failed login security events in ActivityLog
   └── 6. Centralize Password::defaults() across all user forms
   │
   ▼
Phase 3: Authorization & RBAC Hardening (P1)
   ├── 1. Add missing permissions to StaffController CRUD methods
   ├── 2. Create dedicated SuperAdmin middleware wrapper for admin routes
   └── 3. Independent action authorization in all operational modules
   │
   ▼
Phase 4: Tenant & Branch Isolation Hardening (P1)
   ├── 1. Implement centralized tenant access resolution (hasAccessToMarquee)
   ├── 2. Scope branch_id validation to active tenant in StaffController
   └── 3. Comprehensive cross-tenant & cross-branch IDOR testing
   │
   ▼
Phase 5: Full Security Regression & System Verification
   ├── 1. Run complete automated test suite (Feature + Unit)
   └── 2. Deliver final audit report & deployment recommendations
```
