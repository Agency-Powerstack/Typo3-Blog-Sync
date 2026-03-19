<?php

/**
 * JavaScript module registration for EXT:blog_sync.
 *
 * Maps bare module specifiers to physical file paths so TYPO3 can inject them
 * into the page's import map. Modules loaded this way are CSP-compliant because
 * they are loaded as external script files (not inline scripts).
 */
return [
    'imports' => [
        '@agencypowerstack/blog-sync/backend-delete-confirm.js' =>
            'EXT:blog_sync/Resources/Public/JavaScript/backend-delete-confirm.js',
    ],
];
