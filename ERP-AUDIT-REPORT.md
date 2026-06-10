> ## ⚠️ CORRIGENDUM (2026-06-10, after merging origin/master)
> The first pass of this report was written against a **stale worktree**. After merging
> `origin/master`, the live codebase turned out to be far more advanced (SuperAdmin,
> Returns, Materials, BOM, bKash/Nagad, Google OAuth, service-layer refactor, DbHelper).
> Several audited issues were **already fixed** on master and required no change:
> **C1** (cross-tenant approval → SuperAdmin + business-scoped), **C9** (`strftime` →
> driver-aware `DbHelper`), **H2** (schema drift → `fix_missing_columns` with `hasColumn`
> guards), and the **C8 customer-due** ordering.
>
> The issues **re-validated as still present on current master** were fixed in this branch
> (Sprint 1): **C2** role authz, **C3/C4** webhook auth, **C5** order stock scoping,
> **C6** P&L double-count, **C7** transfer money loss, **C12** account balances,
> **C8** status idempotency, **H9** rate limiting, **H1** password-reset views.
> Sections below describe the *original* findings; treat them as the rationale, not the
> current state.

# Eltiz ERP — Complete System Audit Report
**Date:** 2026-06-10 | **Auditor:** Claude (ERP Architect / Security / QA / DBA review)
**Scope:** Full codebase — 23 controllers, 32 models, 75+ views, 5 migrations, routes, middleware, services
**Note:** Worktree is at commit `e5a61f6`; the Tailoring module (8 commits) lives on `origin/master`. Uncommitted Production Task feature exists locally. Audit covers both.

---

## 1. Executive Summary

Eltiz ERP is a Laravel 13 multi-tenant ERP (business_id scoping) for a panjabi factory + e-commerce operation: Orders, POS, Products/WooCommerce, Inventory, Accounts, Reports, Production (piece-rate workers), Tailoring (master), Steadfast courier, SMS.

**Overall verdict:** The functional breadth is impressive for the size, and the UI layer (Tailwind + Alpine, mobile cards + dark mode) is above average. But the system has **critical security holes (cross-tenant data leak, no role permissions), serious accounting integrity flaws (double-counted profit, money-losing transfer logic, duplicate-transaction bugs), and 12 routes that render missing views (500 errors)**. None of these are hard to fix individually — but the financial-integrity ones are silently corrupting your books **today**.

Severity counts: **Critical: 9 · High: 14 · Medium: 16 · Low: 10+**

---

## 2. System Architecture Overview

- **Stack:** Laravel 13 (PHP ≥8.3), Blade + Tailwind + Alpine.js + Chart.js, DomPDF (master), database sessions/cache/queue
- **Tenancy:** Single DB, `business_id` column scoping, enforced manually per-query (no global scopes)
- **Roles:** `owner / admin / manager / staff / employee` — only `employee` is actually restricted (BusinessMiddleware route whitelist). The other four roles are **identical in power**.
- **Local env:** SQLite, debug on, mail=log. Production: Hostinger (`leen.com.bd/fulfillo`).
- **Integrations:** WooCommerce (REST + webhook), Steadfast (REST + webhook), SMS service (provider-based)

## 3. Discovered Module Map

| Module | Routes | Status |
|---|---|---|
| Auth (login/register/approval/password-reset) | 8 | ⚠️ password-reset views missing |
| Dashboard + API stats | 2 | OK |
| Orders (CRUD, status, invoice, export) | 11 | ⚠️ logic bugs |
| Web Orders (WooCommerce) | 4 | OK |
| Products + Categories/Brands/Attributes | 10+3×7 | ⚠️ products.show view missing |
| Inventory (overview, PO, stock, adjustments, barcode, transfers) | 11 | 🔴 transfer destroys stock |
| Accounts (overview, income, expense, salary, transfer) | 10 | 🔴 accounting flaws |
| Reports (13 reports) | 13 | 🔴 2 reports use SQLite-only SQL |
| People (customers, vendors) | 10 | 🔴 vendors view missing |
| Employees + Attendance | 11 | OK (N+1 perf) |
| Production (rates, entries, payroll, payments, admin overview, worker dashboard) | 8 | ⚠️ |
| Production Tasks (assign/submit) — uncommitted | 8 | ⚠️ my-tasks view missing |
| Tailoring (master branch) | 12 | not in this worktree |
| Tasks (generic todo) | 7 | duplicate concept |
| POS (checkout, receipt, barcode scan) | 5 | ⚠️ client-trusted totals |
| Settings (general, business, users, warehouses, statuses, tags, print, subscription) | 20 | 🔴 5 views missing, no authz |
| Integrations (Woo, courier providers, SMS) | 9 | ⚠️ webhook auth |
| Steadfast (send, bulk, track, labels, webhook) | 9 | 🔴 unauthenticated webhook |
| Courier (legacy) + Returns | 7 | dead/orphaned from nav |

---

## 4. CRITICAL Issues

### C1. Cross-tenant user approval leak (Security)
`SettingsController::userManagement()` — pending users query has **no `business_id` filter**: every business owner sees pending users of **all businesses**. `approveUser()` and `rejectUser()` have **no business check and no role check** — any non-employee user of any business can approve or **delete** any pending user system-wide.
**Fix:** add `where('business_id', $bizId)` + `abort_if($user->business_id !== ...)` + restrict to owner/admin. *Complexity: trivial.*

### C2. No role-based authorization anywhere (Security)
`owner/admin/manager/staff` are functionally identical. A `staff` user can: create admin users, change anyone's role (`updateUser` accepts any role — even `owner` — with **zero validation**), edit business settings, delete financial transactions, see all reports. The `users.permissions` JSON column exists but is **never read**. Order-delete is hidden in the UI for staff but the route accepts their POST.
**Fix:** Gate/Policy layer or middleware `role:owner,admin` on settings/accounts/delete routes. *Complexity: medium.*

### C3. Steadfast webhook completely unauthenticated (Security → Financial forgery)
`POST /webhook/steadfast` verifies nothing. Anyone who knows the URL can post `delivery_status` payloads → shipment marked delivered → **fake COD income transactions created in your books** (`addIncomeTransaction`, with hardcoded `created_by = 1`).
**Fix:** shared-secret token in webhook URL or header check. *Complexity: trivial.*

### C4. WooCommerce webhook signature bypass (Security)
`if ($wooSettings->webhook_secret && $signature)` — attacker simply **omits the signature header** and verification is skipped entirely. Also falls back to "first active store" when source doesn't match → cross-tenant injection possible.
**Fix:** reject when secret exists but signature missing/invalid. *Complexity: trivial.*

### C5. Order items not tenant/price validated (Security + Integrity)
`OrderController::store()` accepts any `product_id` (no business check — decrements **other tenants' stock**), and `price`/`total` come from the client without validation against actual product price or `quantity × price`. POS `checkout()` trusts the client-sent grand `total` outright — a tampered request records a ৳10 sale for a ৳10,000 cart. No stock-availability check anywhere → overselling/negative stock.
**Fix:** scope products to business, recompute prices server-side, check stock. *Complexity: medium.*

### C6. P&L double counting — profit is overstated (Accounting)
`profitLoss` = order revenue **+ all income transactions** − expenses. But every order payment **also creates an income transaction**, so sales are counted **twice**. Same flaw in `businessOverview`. Your net profit figures are wrong.
**Fix:** exclude order-linked transactions (`whereNull('order_id')`) from "other income". *Complexity: trivial.*

### C7. Payment transfer = money vanishes (Accounting)
`storeTransfer` records moving cash→bKash as a pure **expense**. The destination never receives income. Every transfer reduces reported profit and corrupts `accountBalance` per-method balances.
**Fix:** create paired expense (from) + income (to) transactions, exclude `TRANSFER-%` refs from P&L. *Complexity: low.*

### C8. Status-change side effects can fire repeatedly (Accounting/Stock)
`updateStatus`: re-selecting "delivered" on a COD order creates a **duplicate full-value income transaction** each time; re-selecting "cancelled" **restores stock again each time**. Cancel flow also decrements customer due using the **already-zeroed** `due_amount` (order is updated first), so customer dues stay inflated forever. Status value isn't validated against the enum (a bad value = SQL error).
**Fix:** guard on previous status, reorder due-decrement before reset, validate status. *Complexity: low.*

### C9. SQLite-only SQL in reports (`strftime`)
`profitLoss` and `customerAnalytics` use `strftime('%Y-%m', ...)` — **SQLite syntax**. Local .env is SQLite so it works on your machine; if production Hostinger runs MySQL these two reports **crash with 500**. (If production also runs SQLite — that's a scalability/concurrency risk of its own.)
**Fix:** use `DATE_FORMAT` per driver or Laravel date helpers. **Verify production DB driver first.** *Complexity: low.*

---

## 5. HIGH Priority Issues

**H1. 12 referenced views don't exist → 500 pages:**
`auth.forgot-password`, `auth.reset-password` (password reset is **fully broken**), `people.vendors` (entire Vendors page), `people.customer-show`, `products.show`, `production.tasks.my-tasks` (a route **workers are whitelisted to use**), `settings.statuses`, `settings.tags`, `settings.payment-history` (linked in routes), `settings.deletion-log`, `settings.payment-accounts`, `settings.keyboard-shortcuts`.

**H2. Schema drift — migrations ≠ production DB.** Code uses columns that exist in no migration: `users.is_approved/approved_by/approved_at`, `shipments.consignment_id`, shipment statuses `approval_pending/partial_delivered/in_review` (enum doesn't allow them in migration), `production_entries.size/color`. A fresh `migrate` produces a broken app. All server-side manual ALTERs must be captured as migrations.

**H3. Purchase Orders: supplier money flow is fiction.** No way to record a vendor payment (`paid_amount` never updated) → Supplier Due report shows everything unpaid forever; `vendors.due_amount` is never written. "Mark received" can be clicked repeatedly → **stock double-counted**. `received_quantity`/partial receive unused. New PO bluntly overwrites product `cost_price` (no weighted average).

**H4. Warehouse transfer destroys inventory.** Transfer only decrements source stock — there is **no destination increment and no per-warehouse stock table**. Multi-warehouse is cosmetic; every "transfer" is a write-off.

**H5. Order edit breaks money math.** `update()` recomputes `subtotal` but **never recomputes `total`**, doesn't adjust the income transaction, and doesn't touch customer due → order totals, books, and customer balances all drift.

**H6. Customer due lifecycle is leaky.** POS sale with due but no selected customer = due tracked nowhere. Walk-in dues, edited orders, deleted orders — none reconcile `customers.due_amount`. Deleted (soft) orders keep their income transactions → reports count revenue for orders that no longer exist.

**H7. Production wage fraud vector.** `production.store` validates `employee_id` as merely `exists:employees,id` — **not scoped to business**, and a worker can post entries for any employee with any quantity, self-reported, no approval step. Rates similarly unscoped (`production_rate_id` from another tenant works).

**H8. Salary module gaps.** `storeSalary`: no duplicate-payment guard, `title` not validated (null → SQL error), `amount` can be negative. Payroll `max(0, netPayable)` hides negative balances and advances **don't carry over months** — a worker who took a big advance in May looks clean in June. Attendance fallback pays full month when no attendance recorded.

**H9. Login has no rate limiting** (no `throttle`), and registration auto-creates business before approval (junk business rows from rejected signups). Mail is `log` so approval/reset emails go nowhere.

**H10. Plaintext secrets.** WooCommerce consumer key/secret, Steadfast api key/secret, SMS keys all stored unencrypted (no `encrypted` cast).

**H11. WooCommerce stock push depends on a queue worker** (`SyncWooCommerceStock` on `low` queue). If Hostinger has no `queue:work` process running, POS sales **never sync stock to the website**.

**H12. accountBalance ignores `cod` and `other`** payment methods — all COD income is invisible in balances.

**H13. ActivityLog table/model exists but nothing ever writes to it.** Zero audit trail for deletes/edits of financial data, while `destroyTransaction` is open to all staff.

**H14. Tailoring (master) + ProductionTask not integrated:** assigning tailoring stages to workers and production tasks are separate silos; tailoring payments use `reference='TAILORING-{id}'` string-matching which is fragile vs an `order_id`-style FK.

---

## 6. MEDIUM Priority Issues

1. **N+1 / query storms:** Employees index = 4 queries × 18 rows; Orders/Steadfast/WebOrders status tabs = 5–7 separate COUNTs (use one `groupBy`); accounts overview = 12 queries for chart; businessOverview = 48 queries.
2. **No composite indexes:** `orders(business_id,status)`, `transactions(business_id,type,transaction_date)`, `production_entries(employee_id,entry_date)` etc. Fine now, slow at 50k+ rows.
3. POS loads **every product** into the page (breaks at ~2-3k products); variable products unsupported in POS.
4. Hover-only row actions (`opacity-0 group-hover`) — unusable via keyboard, awkward on touch laptops.
5. Theme inconsistency: hardcoded `bg-blue-600` in most views vs the `primary-*` theme system added later for Tailoring — two visual languages.
6. Duplicate/overlapping features: generic **Tasks** module vs **ProductionTasks**; **ProductionLog** (unused) vs **ProductionEntry**; legacy **Courier** module vs **Steadfast**; `production.index` entry page vs task submissions (a worker's done work can be double-entered via both paths → double pay).
7. Orphaned pages (routes exist, no nav link): Accounts Overview, Attendance overview, Courier, Returns, reports beyond the 3 linked (reachable only via Reports hub).
8. `vat/tax` columns on orders never calculated anywhere despite Business settings UI for VAT.
9. Order search can't search by SKU/product; no bulk actions (bulk status, bulk Steadfast send exists only on Steadfast page).
10. `returnRefund` report sums `cod_amount` of **current page only** (paginator sum).
11. Trial/subscription: `trial_ends_at` shown but nothing blocks expired trials; subscription page is static.
12. Customer dedupe: WooCommerce `firstOrCreate` by phone/email vs manual creation — no merge tool, duplicates accumulate.
13. Barcode page loads all products; variant barcodes unsupported.
14. No DB backup automation, no scheduled commands at all (no nightly Steadfast `syncStatus`, no Woo periodic sync — all manual buttons).
15. Zero automated tests.
16. `WoocommerceSetting` consumer key masked-value handling re-queries the table 2×; minor.

---

## 7. UX/UI & Responsive Audit (sampled: orders, layout, dashboards)

**Good:** consistent card/table duality (desktop table → mobile cards), status pill tabs with counts, dark mode coverage, scrollable tab rows, empty-states with CTA, mobile bottom nav (5 items).

**Problems:**
- Sidebar: 19 top-level items — too many; Accounts has no Overview link; Reports shows only 3 of 13.
- No toast feedback for AJAX errors in POS (failure = silent 500 JSON).
- Forms lack field-level error display in several modals (only `back()->withErrors` without `@error` blocks in some).
- Date filters: every list uses different patterns (month input vs from/to vs period pills) — standardize.
- No confirmation pattern consistency: some deletes use `confirm()`, transactions delete instantly.
- Mobile: tables that fall back to cards are fine; but POS and payroll tables overflow on <380px; print pages not responsive (acceptable).
- Worker dashboard (Bangla labels) vs admin pages (English) — language mixing without a toggle.

---

## 8. Database & Data-Flow Audit

- **Denormalized balances** (`customers.due_amount`, `total_purchase`, `vendors.due_amount`, `products.stock`) maintained by scattered increments — already inconsistent (see C8/H5/H6). Recommend: derive from ledgers, or centralize mutations in model methods/observers.
- **No stock ledger unification:** stock changes happen via orders (no record), adjustments (recorded), PO (recorded), Woo sync (no record) → can't answer "why is stock 37?". Make every stock mutation write a `stock_adjustments` row.
- **Money has no account dimension:** payment_method string is the only "account". A proper `accounts` table (Cash, bKash, Bank…) with opening balances would fix transfer/balance reporting.
- Enum drift risk (statuses added in code but not in DB enums).
- `production_tasks.pending_quantity` correctly computed not stored ✔.
- Cascade deletes: business delete wipes everything (fine), but `order_items.product_id` has no FK constraint (orphan-tolerant by design — OK since name is snapshotted).

---

## 9. Performance Audit

Biggest wins, in order: (1) collapse status-count query storms into single `GROUP BY` queries; (2) eager-load/aggregate Employees index; (3) add composite indexes listed in §6.2; (4) cache dashboard stats for 60s; (5) chart queries → single grouped query instead of loop-per-month. Page weight is fine (CDN Tailwind/Alpine — consider local build for offline factory use).

---

## 10. Feature Gap Analysis (real business value only)

1. **Role & permission system** (use the existing `permissions` JSON) — prerequisite for hiring office staff safely.
2. **Vendor payments + PO partial receive** — without it supplier accounting is unusable.
3. **Customer due collection screen** (one place: list dues → collect → auto transaction + due decrement; currently only per-order).
4. **Stock availability check + low-stock notification** (data already exists; just alert — email/SMS/dashboard badge).
5. **Approval flow for worker production entries** (admin verifies before payroll counts it) — direct fraud control.
6. **Scheduled automation:** nightly Steadfast status sync, Woo order pull, DB backup, trial-expiry enforcement. (Hostinger cron + Laravel scheduler — one cron line.)
7. **SMS triggers** actually wired to events (settings exist; verify SmsService is invoked on order confirm/shipment).
8. **Order status timeline/history** (who changed what when — pairs with ActivityLog).
9. **Tailoring ↔ ProductionTask bridge** (master): auto-create worker task when tailoring stage = cutting/stitching.
10. AI-lite opportunities (later): demand forecast per product from order history; due-risk scoring for COD customers by district/phone history.

## 11. Cleanup Recommendations

| Item | Action | Why |
|---|---|---|
| Generic Tasks module | Merge into notifications/todo or remove | Overlaps ProductionTasks; both in nav confuse users |
| ProductionLog model + table | Remove | Superseded by ProductionEntry; zero references in controllers |
| Legacy Courier module + Returns page | Fold into Steadfast or remove from routes | Orphaned from nav; Shipment model shared |
| settings: statuses/tags/payment-accounts/keyboard-shortcuts/deletion-log routes | Implement or delete routes | Dead routes that 500 |
| `production.index` manual entry page | Keep but gate behind admin; tasks become the worker path | Double-entry → double pay risk |
| Duplicate COD income paths (OrderController vs SteadfastController) | Single helper with idempotency key | Duplicate income records |

## 12. Scalability Risks

- If production DB is SQLite: single-writer lock will break under 2+ concurrent users — migrate to MySQL ASAP. If MySQL: fix `strftime` (C9).
- All sessions/cache/queue on DB = fine to ~50 users, then move cache to file/redis.
- Single `stock` integer blocks any real multi-warehouse/branch expansion.
- Multi-tenant queries are manual — one forgotten `where business_id` is a breach (already happened: C1). Add a global `BelongsToBusiness` scope/trait.
- No tests = every refactor risks the books.

---

## 13. Recommended Roadmap

**Sprint 1 — Stop the bleeding (1–2 days):**
C1, C2 (route-level role middleware), C3, C4, C6, C7, C8, C9 verify+fix, H12, login throttle. All are small diffs.

**Sprint 2 — Financial integrity (3–5 days):**
C5 (server-side pricing + stock check), H3 (vendor payments + receive-once), H5/H6 (due lifecycle, single `recalcTotals()` + observer), H8 (salary guards, advance carryover), H2 (write drift migrations), H13 (ActivityLog writes on money/delete events).

**Sprint 3 — Broken pages + ops (2–3 days):**
H1 (create or remove 12 views — password reset first), H11 (cron + scheduler: queue via `schedule:work` or supervisor), backups, Steadfast nightly sync, mail config.

**Sprint 4 — UX & performance (ongoing):**
Query-storm fixes, indexes, nav restructure, theme unification, due-collection screen, low-stock alerts, entry-approval flow.

---
*Every issue above cites the exact file/method; ask for any item and I can produce the fix immediately.*
