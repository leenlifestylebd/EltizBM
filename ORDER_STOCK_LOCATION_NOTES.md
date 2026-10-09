> ## ⚠️ SUPERSEDED (2026-10-05) — multi-warehouse removed
>
> The per-order stock-location feature described below was **removed**. Stock is now
> tracked on a single **Main Warehouse** per business: `products.stock` is the only
> authoritative quantity, and there is no warehouse selector, transfer, or
> per-location stock.
>
> Removed: the order-form location selector, "By Warehouse" and "Transfers" pages,
> Settings → Warehouses CRUD, and all per-location stock logic in `StockService`.
> `warehouses`, `warehouse_stocks`, and `orders.warehouse_id` are **retained** so
> historical records stay readable; application code no longer reads or writes them.
> Migration `2026_10_05_000001_keep_single_main_warehouse.php` keeps exactly one
> "Main Warehouse" row per business without dropping any data.
>
> Everything below describes the feature as originally released on 2026-09-20 and
> is kept as the historical record of that release.

# Order stock location selection

Deployed to production on 2026-09-20 via PR #132 at commit `c7cc6d2ddd7da7aeec0e2eaff876d7dce7889510`.

The production-compatible implementation is in `.local-backups/stock-location-release-20260920`, separately reconciled with the deployed September 18 code. It preserves production-specific wholesale, pricing, order numbering, and backdating features. Historical unassigned orders cannot be assigned a guessed stock source. Partial returns are capped, and later cancellation restores only the remainder. Release verification passed 70 tests / 526 assertions, CI, real-form browser checks, and an isolated production-database restore/migration test. See `BACKUP_AND_UPDATE.md` for the verified backups and deployment evidence.

The remaining notes below describe the initial main-workspace implementation and its local verification; use the release checkout for the exact live code.

## Behavior

- New Order has a required warehouse/outlet selector when the business has locations. All lines in one order use that location.
- Product search and cart show availability for the selected location. Size-specific products keep their existing product/SKU identity.
- Saving checks both location stock and total product stock inside a transaction. Missing location rows mean zero stock; stock elsewhere cannot cover a shortage. Duplicate order lines are counted together.
- Order details show the saved source. Editing before shipment restores the old source before deducting from the new source. An unsuccessful edit rolls back stock, lines, and source together.
- Cancellation, return, deletion, manual courier returns, and Steadfast cancellation restore the saved source. Repeated cancellation/return does not restore twice. Changing the default warehouse does not change a saved source.
- Source changes are blocked after shipment or closure. Locations referenced by orders cannot be deleted, including locations used by deleted orders.
- Validation retries preserve the location and cart. The cart layout was checked on mobile.

## Data compatibility

The additive migration `2026_09_20_000001_add_warehouse_id_to_orders.php` adds nullable `orders.warehouse_id` with a foreign key. Historical orders remain unassigned because their true source was not recorded. Historical order stock operations retain the previous default-location fallback; no historical source is guessed or backfilled. Other entry points that omit a source retain their existing behavior.

This feature uses existing product-level warehouse stock records (`variant_id` null). It does not introduce a separate variant-selection interface or split a single order across several locations. Businesses should allocate actual stock to each location using the existing inventory tools before placing source-specific orders.

## Verification

- Private local recovery snapshot: `.local-backups/before-update-20260920-214136` (13,449 files verified; SQLite integrity passed).
- Baseline: 43 tests / 301 assertions. Final: 66 tests / 494 assertions, all passing against the isolated in-memory test database.
- Browser: desktop 1440 x 1100 and mobile 390 x 844, no script errors or horizontal overflow. Validated location switching, shortage rejection, retry input preservation, and real form submission against fictitious data.
- Browser stock example: warehouse 6 and outlet 4; an order for 5 selected warehouse units left warehouse 1, outlet 4, product total 5.
- The new migration has been applied to LOCAL SQLite only. All pre-existing values across 46 business tables were compared before/after and remained unchanged. An additional local database backup was verified immediately before migration.
- Private evidence, screenshots, a pre-task-relative feature patch, and migration verification are in `.local-backups/order-location-qa`.

## Release boundary

The main workspace already contains unrelated local changes and differs from the September 18 deployed release documented in `BACKUP_AND_UPDATE.md`. Do not deploy this checkout wholesale. The Git remote refresh was unavailable in this session (repository access failed). Before a production release, reconcile only this feature with the actual deployed revision, perform the existing fresh backup/restore verification workflow, and test the additive migration on a restored production database. No production migration, stock adjustment, courier request, or deployment was performed for this task.
