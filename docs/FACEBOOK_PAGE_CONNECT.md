# Facebook Page connection

## Client flow
Settings → API & Automation → Connect Facebook → Meta consent → choose one Page → connect.
Reconnect replaces only this business's Page. Check access verifies a Page read; it does not test messaging permissions or deliver a message. Disconnect removes the local Page token and metadata, leaving other integrations and businesses unchanged. It does not revoke the user's app-wide Meta consent, which may be used by other businesses. Users can remove that consent in Facebook Business Integrations.

## Service-owner setup (once per deployment)
1. Create a Meta developer app for the business service and configure Facebook Login for Business with a **User access token** configuration and Pages as the requested asset. This implementation uses `/me/accounts`; a system-user token configuration is not supported.
2. Configure HTTPS app domain, privacy policy, terms, user-data deletion instructions/callback, and Meta-required business information. Complete App Review / Advanced Access and business verification as required before onboarding external clients. Development-mode access is only suitable for eligible app-role test accounts and approved assets.
3. Configure these permissions in the login configuration: `pages_show_list`, `pages_read_engagement`, `pages_read_user_content`, `pages_manage_engagement`, `pages_messaging`, `pages_manage_metadata`. The callback checks that all are granted. These permissions do not grant ad-account access or automatically resolve ad metadata.
4. Add the exact Valid OAuth Redirect URI: `https://app.leen.com.bd/settings/automation/facebook/callback`.
5. Set server-only `META_APP_ID`, `META_APP_SECRET`, `META_LOGIN_CONFIG_ID`, `META_REDIRECT_URI`, and `META_GRAPH_VERSION`. The default version is pinned to `v24.0`; select a supported version for your Meta app and test it. Never paste the service-owner secret into client settings. Refresh Laravel config after configuration changes. Missing configuration leaves the client button disabled.
6. Keep HTTPS sessions and a shared server-side OAuth cache. `META_OAUTH_CACHE_STORE=file` suits the current single-host deployment; choose a shared cache when scaling across hosts. The Page list is encrypted, cached for 10 minutes and removed on completion/disconnect/restart. Session state is bound to user and business, time limited and consumed before token exchange. Public views receive only Page IDs and names.
7. Test real consent, denied permission, Page selection, reconnect, logout during consent, access removal and disconnect with the configured Meta app. Automated tests mock Meta and cannot replace this check.

## Automation boundary
This feature connects and stores Page access; it does not subscribe Page webhooks or turn on automatic replies. Configure Meta webhook verification/signature validation and the n8n event workflow separately, then subscribe the selected Page. The global app secret stays server-side and is not exposed through `/automation/config`. Existing per-client manual secrets remain for backward compatibility, but must not be used as a substitute for the service-owner OAuth app secret.

Trusted n8n can retrieve the current `facebook_page_id` and encrypted-at-rest `facebook_page_token` using the existing, separately permissioned `/api/v1/bot/automation/config`. A workflow must fetch current configuration and fail closed when the Page token is absent; do not keep an independent stale credential after Disconnect. It must route each event by the selected Page and business, never by user-provided business IDs. The same Page must not be activated for two clients without an explicit routing policy.

## Data and deployment
No schema migration or existing business-data rewrite. Settings updates preserve OAuth metadata and prevent manual Page ID/token fields from overriding an OAuth connection. Existing API tokens, permissions, orders and accounting data are unchanged. Deployment still requires the existing backup-first workflow. Never log OAuth callback query strings, provider HTTP URLs, access tokens or temporary cache payloads. Redact callback query strings in proxy/access logs and disable HTTP tracing for token exchanges.

## Sources
- https://developers.facebook.com/docs/facebook-login/facebook-login-for-business/
- https://www.postman.com/meta/facebook/request/bqfxwbp/get-access-tokens-of-pages-you-manage

Meta developer documentation returned HTTP 429 during implementation. Verify current dashboard requirements and production approval with the real app before enabling the connection.

The service worker excludes automation settings/OAuth routes and respects `private` and `no-store`. Cache version v2 removes the previous cache at activation. Run `node --test tests/service-worker-cache.cjs` to verify these rules.
