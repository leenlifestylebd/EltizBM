# Client API and automation settings

## Status and rollout

Implemented in code; this change does not deploy to production, register provider webhooks,
connect Meta accounts, or start n8n workflows. The production migration gate is intentionally
unchanged until an isolated restore/rehearsal verifies the new migration.

1. Capture and independently verify current production backups, including APP_KEY.
2. Rehearse `2026_10_23_000001_create_business_automations.php` against an isolated restored
   MariaDB database. It only adds `business_automations` and `bot_api_requests`.
3. Review and allow this migration in `.github/scripts/deploy-remote.sh` only after that rehearsal.
4. Deploy application and migration together. Keep API consumers paused during the transition.
5. Each business owner/admin opens **Settings → API & Automation**. Generate a token, copy it
   once, select permissions, enable API access, and save.
6. Put the token in an n8n Bearer credential and test `/health` before enabling write workflows.

**Breaking authentication change:** the old shared `BOT_API_TOKEN` / `BOT_BUSINESS_ID` environment
configuration is no longer accepted. No request falls back to the first business. Existing API
consumers need a new business-specific token from Settings. No existing environment secret is
modified by this change.

## Client settings

Only active business owners/admins can view or change credentials. Settings always use the logged-in
user's business, never a supplied business ID. Provider fields have empty instructional placeholders.
Saved secrets never populate HTML fields and are excluded from validation session input.
Blank secret fields preserve values; explicit Remove clears them. Secrets are encrypted with
Laravel APP_KEY. API tokens are cryptographically random, SHA-256 hashed at rest and shown once.
Replacing/revoking a token invalidates its old value. The issuing user must remain active and have
owner/admin role. Each business has one active API token in this version.

Fields: OpenRouter API key/model, Telegram bot token and allowed staff IDs, Facebook Page ID/token,
Meta app secret/webhook verify token, n8n workflow webhook URL and its authentication secret.
These are storage/configuration fields, not proof that credentials are valid. The downstream
workflow must enforce the Telegram allowlist and validate Meta signatures.

## Requests

Base URL: `https://app.leen.com.bd/api/v1/bot`

Headers:

```http
Authorization: Bearer <business-token-from-settings>
Accept: application/json
Content-Type: application/json
Idempotency-Key: telegram-message-123456
```

The Idempotency-Key header is mandatory for writes, 8–128 ASCII letters/digits or `_.:-`.
Reuse it with the exact same path and JSON bytes when retrying. Same business/key/request replays
the original successful response; a changed request returns 409. Validation failures are not stored.
Write operations and their response record commit in one database transaction. API writes serialize
per business. Rate limit: 60 requests/minute/business, not shared across all clients on one n8n IP.

| Method | Route | Permission |
| --- | --- | --- |
| GET | /health | Any enabled valid token |
| GET | /products?search=Fabric&limit=10 | catalog:read |
| GET | /products/{id} | catalog:read |
| GET | /stock?product_id=123 | catalog:read |
| GET | /stock?variant_id=456 | catalog:read |
| GET | /orders/{orderNumber} | orders:read |
| POST | /orders | orders:write |
| GET | /reports/summary | reports:read |
| GET | /account-categories | transactions:write |
| POST | /transactions | transactions:write |
| GET | /automation/config | automation:read |

`/automation/config` deliberately exposes this business's decrypted provider credentials ONLY
when the owner enables the separate `automation:read` permission. Never expose that token to
customers or frontend scripts. In n8n, disable execution-data storage for steps that retrieve secrets,
and avoid logging or forwarding raw credentials to an AI provider. Other endpoints never return them.
All authenticated API responses use `Cache-Control: no-store, private`.

## Order example

```json
{
  "customer": {"name": "Test Customer", "phone": "01700000000", "address": "Mirpur, Dhaka"},
  "items": [{"product_id": 123, "quantity": 2}],
  "source": "telegram",
  "payment_method": "cod",
  "shipping_charge": 80,
  "paid_amount": 0
}
```

Resolve real product/variant IDs from this business's catalog. Prices come from ERP; client-provided
prices are ignored. Orders use EltizBM's existing StockService for auditable/reversible stock movement.
The current ERP uses one product-level stock location; variant stock responses report that authoritative
product stock too. Integer quantities only: fractional yards need a separate ERP quantity migration.
Paid amount must not exceed total. The API does not verify that a payment actually happened: n8n must
only send a verified/approved payment amount. It creates a pending order, not an automatic delivery.

## Settled income/expense example

```json
{
  "type": "expense",
  "category_id": 123,
  "title": "Office rent",
  "amount": "5000.00",
  "transaction_date": "2026-10-23",
  "payment_method": "cash",
  "notes": "Approved via Telegram"
}
```

Use a category ID returned by `/account-categories`; it must belong to the token's business and match
income/expense type. Positive BDT amount, at most two decimal places. This endpoint is for simple
settled transactions only. Credit bills, payroll settlement, stock/material purchases, production jobs,
transfers, refunds and order payments must use their dedicated business flows. Do not encode them as
ordinary expenses to bypass their ledgers. Approval and ambiguity handling belong in the n8n workflow.

## Verification

`php vendor/phpunit/phpunit/phpunit tests/Feature/BusinessAutomationTest.php`

Tests use an isolated in-memory SQLite database, not local or production business data.
MariaDB restore/rehearsal and live provider/n8n end-to-end tests remain required before deployment.
