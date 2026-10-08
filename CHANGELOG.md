# Changelog

## 2.0.0 (2026-10-08)

The connector is now a SoftAware module: package `softaware/module-arasta-live-chat`, module
`Softaware_ArastaLiveChat`, namespace `Softaware\ArastaLiveChat`. The contract with Arasta is unchanged: REST URLs and
response shapes, the capabilities reported by `/V1/platform/version`, the identity token (HS256, claims `sub`,
`email`, `iat`, `exp`) and the `data-customer-token` / `data-cart-id` attributes, the order link body and its
`X-Platform-Signature` header, and the `platform_conversation_id` columns.

### Changed

- Renamed from `platform/module-connector` / `Platform_Connector` (see "Upgrading from 1.x" in the README). The two
  packages conflict in Composer.
- Depends on `softaware/module-core` ^1.0. Composer: Mage-OS mirror repository, `version` field and PHPStan setup
  removed; licence proprietary.
- The loader tag now matches Arasta's embed code: `data-store-id` (was `data-store-key`), `async`, default loader URL
  `https://app.arasta.io/widget/v1/assets/loader.js`.
- Settings moved from `platform_connector/*` to `softaware_arasta_live_chat/*` (`widget/store_key` is now
  `widget/store_id`). A data patch copies saved values; values only in env.php/config.php under the old paths are
  still read as a fallback.
- ACL resource `Platform_Connector::api` is now `Softaware_ArastaLiveChat::api` ("Arasta Live Chat API", under
  Softaware > Arasta Live Chat). The data patch grants it to every role and integration that had the old one.
- Layout block `platform.connector.widget` is now `softaware.arasta.widget`.

### Added

- PHP 8.5 support (PHP 8.2 – 8.5).
- Admin settings: Stores > Configuration > Softaware > Arasta Live Chat (Enabled, Widget Loader URL, Store ID,
  Signing Secret stored encrypted, Identity Token Lifetime), per website and store view, with their own ACL resource
  `Softaware_ArastaLiveChat::config`. The secret must be at least 32 characters and the loader URL must be https.
- Enabled switch: when off, no widget, no identity token, no quote tagging and no order links.
- Hyvä support: vanilla JavaScript template using Hyvä private content (no RequireJS), picked through
  `hyva_default.xml`; Hyvä CSP-safe (inline script registered with `HyvaCsp`).
- Content Security Policy: `app.arasta.io` whitelisted (script, connect incl. `wss://`, img, style, font, frame); a
  custom loader host from the admin is allowed on the storefront automatically.
- Expired identity tokens cached in the browser are refreshed before they are handed to the widget, and a fresh
  token is fetched shortly before the current one expires.
- The identity section is reloaded when a guest gets a cart and after an order is placed, so `data-cart-id` is
  current on Luma.
- The signing secret is marked as sensitive (not written to `config.php` by `app:config:dump`).

### Fixed

- Orders were not linked to conversations on Magento 2.4.x: `QuoteManagement` drops custom columns when it builds the
  order, so `platform_conversation_id` never reached the order and no order link was sent. The ID is now copied on
  `sales_model_service_quote_submit_before`.
- The token expiry reported to the browser now matches the token's `exp` when the lifetime is set below 60 seconds.
- A signing secret shorter than 32 characters set outside the admin no longer breaks the customer-data request; no
  token is issued and a warning is logged.
- The quote attribute service narrows the cart repository's `CartInterface` to `Quote` before using its data
  accessors (fix made after the 1.1.0 tag).

## 1.1.0 (2026-10-08) — as `platform/module-connector`

First release in this repository (package `platform/module-connector`, module `Platform_Connector`):

- `GET /V1/platform/invoices/:invoiceId/pdf` and `GET /V1/platform/version` for the Arasta integration
  (ACL `Platform_Connector::api`).
- Signed customer identity (`data-customer-token`) and the guest masked cart ID (`data-cart-id`) on the widget loader
  tag, from the `platform-identity` customer-data section.
- `POST /V1/platform/quote/attribute` (anonymous) tags the shopper's active cart with the Arasta conversation ID;
  `platform_conversation_id` on `quote` and `sales_order`.
- On `sales_order_place_after`, orders whose cart carried the ID are posted to Arasta with `X-Platform-Signature`.
- Settings only through `config:set` (`platform_connector/*`), no admin UI.
