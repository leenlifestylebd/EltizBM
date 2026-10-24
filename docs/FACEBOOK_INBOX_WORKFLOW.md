# Facebook inbox and n8n review workflow

Status: implementation prepared locally. Import and live end-to-end checks are still required. This workflow creates drafts and can apply owner-enabled product rules. Other replies require a human to use Approve & Send. It does not create orders.

## Flow

Meta signed webhook -> encrypted per-business inbox -> n8n claims one event -> business settings and partial catalog -> OpenRouter draft -> validated draft/human handover -> ERP review inbox -> owner-enabled price/stock rule or manual Approve & Send.

## Server setup

Back up production files/database and verify an isolated restore before applying the additive `2026_10_24_000001_create_facebook_inbox_events.php` migration. Do not reset existing tables. Configure `META_WEBHOOK_VERIFY_TOKEN` with a new random secret of at least 32 characters. Keep the existing global Meta App Secret. Rebuild configuration cache using the deployment procedure.

In Meta's Page webhook configuration, use `https://app.leen.com.bd/api/webhooks/facebook` as the callback and the matching verification token. Subscribe to `messages`, `feed`, and `messaging_referrals`. In the business dashboard, connect the Page and enable message intake. Each Page may receive into only one business. Pausing intake preserves existing events and drafts.

## n8n setup

Import `automation/n8n/facebook-review.json` (inactive by default). Create an HTTP Header Auth credential with name `Authorization` and value `Bearer YOUR_BUSINESS_ERP_TOKEN`. Assign it to Claim event, Read settings, Read catalog, Save for review, and Apply enabled product rule. The ERP token needs `automation:write`, `automation:read`, `catalog:read`, and `facebook:send` for the guarded automatic-reply node. Use a separate workflow and credential for each business.

In ERP Settings > API & Automation, save an OpenRouter API key and exact model ID. The workflow reads them at execution time. The n8n management API key is different from the ERP token and OpenRouter key; it is only for administering workflows.

The export disables saved successful, failed, manual and progress execution data because settings contain credentials. Keep these settings when importing. Restrict editor access; manual runs can still display live data in the editor. Do not pin credential outputs or publish execution data. No credentials are embedded in the export.

Run one controlled test message from a separate Facebook user with appropriate access for the Meta app mode. Confirm exactly one inbox entry, then execute the workflow manually. Verify a draft or handover appears in `/settings/automation/facebook/inbox`, and no customer reply is sent. Activate the minute schedule only after the test passes.

## Current limits

- One event per minute per workflow; a claim expires in 10 minutes and becomes handover after three failed attempts.
- The catalog is limited to 30 recent products. Only explicit name/SKU matches provide draft context; missing or ambiguous context always goes to handover. This is a conservative first version, not complete catalog search.
- Owners can map numeric Ad IDs or Page Post IDs to a product in Facebook product rules. Standalone ad referrals apply to the same Page/sender for 24 hours. A message saying only "price?" requires a verified rule; otherwise it stays in human review.
- Attachments and non-text events go directly to handover. Historical conversations, edited/deleted comments, and standalone referral/postback events are not synchronized.
- Provider errors or invalid model JSON become handover; infrastructure errors are retried through the expiring claim.
- Review and edit drafts in ERP, then Approve & Send. Replies have encrypted send records and duplicate protection. Confirmed provider rejection may be retried; network errors, malformed successes and server failures become uncertain and must be checked in Meta Business Suite before any further action. Messenger sends are limited to 24 hours from the inbound message. Synthetic setup events cannot send. Telegram intake and order/accounting workflows are separate.
- Meta app review/permission access is still required for external clients as applicable; connecting an administrator's Page alone is not proof of approval.

## Validation

Run `php vendor/phpunit/phpunit/phpunit tests/Feature/FacebookInboxTest.php` and `node --test tests/Feature/facebook-workflow.test.cjs`. These verify local behavior only; they do not replace an actual n8n import and Meta/OpenRouter end-to-end test.

## Owner-enabled product replies

The Ad/Post rules page scopes each rule to a business and its connected Page. Select an active ERP product, enter the exact numeric source ID, and explicitly check automatic replies. Re-save the same source/ID with the checkbox off to disable sending. Removing a rule does not remove events or orders. Conflicting Ad/Post product mappings fail closed.

Only an allowlisted short price/availability question qualifies. The server generates the actual reply from the current product name, price and stock; it never sends arbitrary AI output automatically. Discounts, order requests, delivery promises, complaints and compound questions require human approval. No rules are enabled by the deployment. The current configuration is not a general autonomous sales agent.
