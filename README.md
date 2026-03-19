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
3. You will be redirected to [agency-powerstack.com](https://agency-powerstack.com) to authorize the connection.
4. After authorization, you are redirected back to TYPO3 where the connection is automatically confirmed.

### 3. Set the Blog Storage Folder

After connecting, open the connection record and set the **Blog Storage Folder** to the page where synchronized posts should be created.

### 4. Enable Sync

Toggle **Sync aktiviert** on the connection record to start receiving webhooks.

## About Agency Powerstack

**Agency Powerstack** is a platform for marketing agencies that helps manage and publish content across multiple CMS systems — including TYPO3, Contao, and others.

- Website: [agency-powerstack.com](https://agency-powerstack.com)
- Contact: [info@agency-powerstack.com](mailto:info@agency-powerstack.com)

---

## Support

For questions or issues regarding this extension, please contact us at [info@agency-powerstack.com](mailto:info@agency-powerstack.com) or visit [agency-powerstack.com](https://agency-powerstack.com).
