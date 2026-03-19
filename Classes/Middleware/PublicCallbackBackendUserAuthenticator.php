<?php

declare(strict_types=1);

namespace AgencyPowerstack\BlogSync\Middleware;

use TYPO3\CMS\Backend\Middleware\BackendUserAuthenticator;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\RateLimiter\RateLimiterFactory;

final class PublicCallbackBackendUserAuthenticator extends BackendUserAuthenticator
{
    public function __construct(
        Context $context,
        LanguageServiceFactory $languageServiceFactory,
        RateLimiterFactory $rateLimiterFactory,
    ) {
        parent::__construct($context, $languageServiceFactory, $rateLimiterFactory);

        // TYPO3 v14 uses a hardcoded list for public backend routes.
        // Add all three EXT:blog_sync routes so neither the browser OAuth redirect
        // nor server-to-server webhook calls get forced to /typo3/login.
        $blogSyncPublicPaths = [
            '/blog-sync/callback',
            '/blog-sync/api/push',
            '/blog-sync/disconnect',
            '/blog-sync/api/languages',
        ];
        foreach ($blogSyncPublicPaths as $path) {
            if (!in_array($path, $this->publicRoutes, true)) {
                $this->publicRoutes[] = $path;
            }
        }
    }
}
