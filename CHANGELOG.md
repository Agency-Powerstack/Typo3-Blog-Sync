# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] – 2026-03-18

### Initial Release

- OAuth-like connect flow with Agency Powerstack platform
- Webhook-based blog post push and update via REST API (`POST /blog-sync/api/push`)
- Disconnect webhook support (`DELETE /blog-sync/disconnect`)
- Site language discovery endpoint (`GET /blog-sync/api/languages`)
- FAL-integrated image download with magic-byte validation (JPEG, PNG, GIF, WebP, AVIF; SVG excluded)
- Backend module for connection management (admin only)
- Audit log table (`tx_blogsync_log`) with per-sync status, counts, and details
- Bearer token authentication with timing-safe comparison (`hash_equals`)
- Defense-in-depth: `connection_id` mismatch detection on push endpoint
- German and English localization (XLIFF)
- TYPO3 14.x compatibility, PHP 8.2+
- Requires EXT:blog (`t3g/blog`)
