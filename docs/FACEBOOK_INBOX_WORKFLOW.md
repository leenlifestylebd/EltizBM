# Facebook inbox and n8n review workflow

Status: implementation prepared locally. Import and live end-to-end checks are still required. This workflow does not send Facebook replies or create orders.

## Flow

Meta signed webhook -> encrypted per-business inbox -> n8n claims one event -> business settings and partial catalog -> OpenRouter draft -> validated draft/human handover -> ERP review inbox.

## Server setup

Back up production files/database and verify an isolated restore before applying the additive `2026_10_24_000001_create_facebook_inbox_events.php` migration. Do not reset existing tables. Configure `META_WEBHOOK_VERIFY_TOKEN` with a new random secret of at least 32 characters. Keep the existing global Meta App Secret. Rebuild configuration cache using the deployment procedure.

In Meta's Page webhook configuration, use `https://app.leen.com.bd/api/webhooks/facebook` as the callback and the matching verification token. Subscribe to `messages` and `feed`. In the business dashboard, connect the Page and enable message intake. Each Page may receive into only one business. Pausing intake preserves existing events and drafts.

## n8n setup

Import `automation/n8n/facebook-review.json` (inactive by default). Create an HTTP Header Auth credential with name `Authorization` and value `Bearer YOUR_BUSINESS_ERP_TOKEN`. Assign it to Claim event, Read settings, Read catalog, and Save for review. The ERP token needs `automation:write`, `automation:read`, and `catalog:read` only. Use a separate workflow and credential for each business.

In ERP Settings > API & Automation, save an OpenRouter API key and exact model ID. The workflow reads them at execution time. The n8n management API key is different from the ERP token and OpenRouter key; it is only for administering workflows.

The export disables saved successful, failed, manual and progress execution data because settings contain credentials. Keep these settings when importing. Restrict editor access; manual runs can still display live data in the editor. Do not pin credential outputs or publish execution data. No credentials are embedded in the export.

Run one controlled test message from a separate Facebook user with appropriate access for the Meta app mode. Confirm exactly one inbox entry, then execute the workflow manually. Verify a draft or handover appears in `/settings/automation/facebook/inbox`, and no customer reply is sent. Activate the minute schedule only after the test passes.

## Current limits

- One event per minute per workflow; a claim expires in 10 minutes and becomes handover after three failed attempts.
- The catalog is limited to 30 recent products. Only explicit name/SKU matches provide draft context; missing or ambiguous context always goes to handover. This is a conservative first version, not complete catalog search.
- Ad/Post IDs are retained in the inbox, but ad-to-product mapping is not yet implemented. A message saying only "price?" requires human review.
- Attachments and non-text events go directly to handover. Historical conversations, edited/deleted comments, and standalone referral/postback events are not synchronized.
- Provider errors or invalid model JSON become handover; infrastructure errors are retried through the expiring claim.
- Review drafts in ERP and reply through Meta Business Suite. An in-app approval/send action, Telegram intake, and autonomous order/accounting workflows remain separate work.
- Meta app review/permission access is still required for external clients as applicable; connecting an administrator's Page alone is not proof of approval.

## Validation

Run `php vendor/phpunit/phpunit/phpunit tests/Feature/FacebookInboxTest.php` and `node --test tests/Feature/facebook-workflow.test.cjs`. These verify local behavior only; they do not replace an actual n8n import and Meta/OpenRouter end-to-end test.
