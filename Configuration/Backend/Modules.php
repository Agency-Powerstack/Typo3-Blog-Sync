<?php

use AgencyPowerstack\BlogSync\Controller\BlogSyncModuleController;

return [
    'web_blogsync' => [
        'parent' => 'web',
        'position' => ['bottom' => true],
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/web/blogsync',
        'labels' => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_mod.xlf',
        'iconIdentifier' => 'blogsync-module',
        'routes' => [
            '_default' => [
                'target' => BlogSyncModuleController::class . '::handleRequest',
            ],
        ],
    ],
];
