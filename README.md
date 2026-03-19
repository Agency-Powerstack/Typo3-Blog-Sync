# Agency Powerstack Blog Sync

[![TYPO3](https://img.shields.io/badge/TYPO3-14.x-orange.svg)](https://typo3.org/)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-proprietary-red.svg)](https://agency-powerstack.com)

A TYPO3 extension that synchronizes blog posts from [Agency Powerstack](https://agency-powerstack.com) to your TYPO3 installation via secure REST API webhooks.

---

## Requirements

- TYPO3 **^14.0**
- PHP **^8.2**
- [`t3g/blog`](https://extensions.typo3.org/extension/blog) extension (EXT:blog) installed and configured

---

## Installation

```bash
composer require agencypowerstack/blog-sync
```

Then activate the extension in the TYPO3 Extension Manager or via CLI:

```bash
vendor/bin/typo3 extension:activate blog_sync
```

---

## Setup

### 1. Configure the Blog Storage Folder

In your TYPO3 page tree, create or choose an existing **Blog** folder (doktype: Folder or Blog). This is where synchronized posts will be stored.

### 2. Connect to Agency Powerstack

1. Open the **Agency Powerstack Blog Sync** module in the TYPO3 backend (under *Web*).
2. Click **Connect with Agency Powerstack**.
3. You will be redirected to [app.agency-powerstack.com](https://app.agency-powerstack.com) to authorize the connection.
4. After authorization, you are redirected back to TYPO3 where the connection is automatically confirmed.

### 3. Set the Blog Storage Folder

After connecting, open the connection record and set the **Blog Storage Folder** to the page where synchronized posts should be created.

### 4. Enable Sync

Toggle **Sync aktiviert** on the connection record to start receiving webhooks.

---

## How It Works

### Connect Flow

1. The TYPO3 admin clicks *Connect* — TYPO3 redirects to Agency Powerstack with a `callback_url`.
2. Agency Powerstack authenticates the user, generates a `connection_id`, and redirects back to TYPO3.
3. TYPO3 generates a 256-bit random API key, persists the connection, and redirects the user to the Agency Powerstack confirmation URL — passing the API key and the TYPO3 site URL.
4. Agency Powerstack stores the API key and will use it for all future webhook calls.

### Blog Sync (Webhook)

Agency Powerstack sends an HTTP `POST` to `/blog-sync/api/push` with a Bearer token and the blog post data. TYPO3 imports (or updates) the post — including images via FAL — and responds with the post UID.

Posts are identified by an `external_id` field on the `pages` table. Re-sending an existing post updates it in place.

---

## API Endpoints

All endpoints are secured. No backend login is required — authentication uses Bearer tokens.

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/blog-sync/callback` | OAuth-like connect callback (called by Agency Powerstack during setup) |
| `POST` | `/blog-sync/api/push` | Receives and imports a blog post |
| `DELETE` | `/blog-sync/disconnect` | Disconnects and removes a connection |
| `GET` | `/blog-sync/api/languages` | Returns all configured site languages |

### Authentication

Every API call (except `/blog-sync/callback`) requires an `Authorization: Bearer <api_key>` header. The API key is generated automatically during the connect flow — no manual configuration required.

---

## Security

- **Timing-safe comparison** — API keys are validated with `hash_equals()` to prevent timing attacks.
- **Soft-delete protection** — Deleted connections are excluded from authentication immediately.
- **Origin validation** — The connect callback validates `confirm_url` against the known Agency Powerstack base URL to prevent open-redirect attacks.
- **Image validation** — Downloaded images are verified via magic bytes; SVGs are explicitly rejected to prevent XSS.
- **SSL verification** — All outgoing HTTPS calls use peer certificate verification.
- **Parameterized queries** — All database operations use TYPO3's ConnectionPool with prepared statements.

---

## About Agency Powerstack

**Agency Powerstack** is a platform for marketing agencies that helps manage and publish content across multiple CMS systems — including TYPO3, Contao, and others.

- Website: [agency-powerstack.com](https://agency-powerstack.com)
- Contact: [info@agency-powerstack.com](mailto:info@agency-powerstack.com)

---

## Support

For questions or issues regarding this extension, please contact us at [info@agency-powerstack.com](mailto:info@agency-powerstack.com) or visit [agency-powerstack.com](https://agency-powerstack.com).
