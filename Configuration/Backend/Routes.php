<?php

use AgencyPowerstack\BlogSync\Controller\ConnectCallbackController;
use AgencyPowerstack\BlogSync\Controller\Api\BlogPushController;
use AgencyPowerstack\BlogSync\Controller\Api\DisconnectController;
use AgencyPowerstack\BlogSync\Controller\Api\LanguagesController;

/**
 * Backend routes for EXT:blog_sync.
 *
 * All routes are declared 'public' so TYPO3 does not require a backend
 * session for them:
 *  - callback:      OAuth-like connect flow, called by the browser after frontend auth
 *  - api/push:      Webhook from Agency Powerstack (server-to-server, Bearer token)
 *  - disconnect:    Disconnect webhook from Agency Powerstack (server-to-server, Bearer token)
 *  - api/languages: Returns configured site languages (called once at connection confirm)
 *
 * Security for push/disconnect/languages is enforced inside each controller via ApiAuthenticator
 * (Bearer token + hash_equals constant-time comparison).
 */
return [
    'typo3_blog_sync_callback' => [
        'path'   => '/blog-sync/callback',
        'access' => 'public',
        'target' => ConnectCallbackController::class . '::callback',
    ],
    'typo3_blog_sync_api_push' => [
        'path'   => '/blog-sync/api/push',
        'access' => 'public',
        'target' => BlogPushController::class . '::push',
    ],
    'typo3_blog_sync_disconnect' => [
        'path'   => '/blog-sync/disconnect',
        'access' => 'public',
        'target' => DisconnectController::class . '::disconnect',
    ],
    'typo3_blog_sync_api_languages' => [
        'path'   => '/blog-sync/api/languages',
        'access' => 'public',
        'target' => LanguagesController::class . '::languages',
    ],
];
