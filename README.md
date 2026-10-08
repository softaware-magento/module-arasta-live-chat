# Magento connector module (`Platform_Connector`)

Minimal Adobe Commerce / Magento Open Source module (spec §7.4). Scope is fixed:

| Requirement | What it does |
| --- | --- |
| R-MOD-01 | `GET /V1/platform/invoices/:invoiceId/pdf` — the store's own invoice PDF as base64 (`file_name`, `content_type`, `content`). |
| R-MOD-02 | Signed customer identity: for logged-in customers, adds `data-customer-token` (HS256 JWT, spec §9.4) to the widget loader tag. The token comes from a private customer-data section, so full-page-cached HTML never contains it. |
| R-MOD-03 | `GET /V1/platform/version` — module version, Magento version and capabilities, used by capability discovery. |
| R-PD-02 (Phase 2) | `POST /V1/platform/quote/attribute` `{conversationId, cartId?}` — called by the widget loader from the storefront (anonymous route; the customer session or the guest's masked quote id identifies the cart). Writes `platform_conversation_id` on the active quote; `etc/fieldset.xml` copies it to the order. |
| R-PD-03 (Phase 2) | On `sales_order_place_after`, an order whose quote carried the attribute is posted to `{api}/public/v1/integrations/magento/orders` `{storeId, orderId, incrementId, grandTotal, currency, conversationId}`, signed with the store signing secret (`X-Platform-Signature: sha256=<hex HMAC of the body>`). Best effort, 3 s timeout, never blocks checkout. |

No admin UI, no RMA helpers, no other event pushing. Stores without the module work fully, except that invoice PDFs
are not available and orders are not linked to conversations. The quote/order column is declared in `etc/db_schema.xml`
(`setup:upgrade` adds it).

Supported: Magento Open Source / Adobe Commerce 2.4.7+ on PHP 8.2–8.4.

## Install

**Composer (recommended)** — from the zip or a private repository:

```bash
composer require platform/module-connector:^1.0
bin/magento module:enable Platform_Connector
bin/magento setup:upgrade
bin/magento setup:di:compile            # production mode only
bin/magento setup:static-content:deploy # production mode only
bin/magento cache:flush
```

**Manual** — copy this directory to `app/code/Platform/Connector/` and run the same `bin/magento` commands.

## Grant API access to the integration

The endpoints are protected by one ACL resource, **Platform connector API** (`Platform_Connector::api`).

1. Admin → System → Extensions → Integrations → edit the integration you created for the platform.
2. API tab → tick **Platform connector API** (next to the Sales, Customers, Catalog, Content and Stores resources you already granted).
3. Save, then **Reauthorize** the integration. The tokens stay the same; the new permission applies after re-authorising.

## Signed customer identity (optional)

The widget recognises logged-in customers when the store signs a short-lived token with the store's signing secret
(copy it from the dashboard: Stores → your store → Widget). Keep the secret out of the database:

```bash
bin/magento config:set --lock-env platform_connector/identity/signing_secret '<signing secret>'
bin/magento config:set --lock-env platform_connector/widget/loader_url 'https://…/loader.js'
bin/magento config:set --lock-env platform_connector/widget/store_key '<store key>'
bin/magento cache:flush
```

The module then renders the widget loader on every storefront page and adds `data-customer-token` for logged-in customers
(and `data-cart-id`, the guest's masked quote id, for the quote attribute endpoint). The same secret and store key sign
the order link posted at checkout (R-PD-03); the platform API origin is derived from `loader_url`.
Remove the widget `<script>` tag from your theme if you added it by hand before. Stores that cannot install the module can
sign the token themselves: see `docs/integrations/magento-signed-identity.md`.

## Development

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 install --ignore-platform-reqs --no-plugins
docker run --rm -v "$PWD":/app -w /app php:8.4-cli vendor/bin/phpunit
docker run --rm -v "$PWD":/app -w /app php:8.4-cli vendor/bin/phpstan analyse
```
