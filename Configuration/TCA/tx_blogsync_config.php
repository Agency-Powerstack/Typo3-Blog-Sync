<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_config',
        'label' => 'site_url',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'connection_id,account_email,site_url',
        'iconfile' => 'EXT:blog_sync/Resources/Public/Icons/Extension.svg',
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tab.configuration,
                    blog_storage_folder, sync_enabled, render_h1_title, last_sync,
                --div--;LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tab.authentication,
                    site_url, account_email, connection_id, api_key,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    hidden
            ',
        ],
    ],
    'columns' => [
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => '',
                        1 => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'connection_id' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_config.connection_id',
            'description' => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_config.connection_id.description',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'readOnly' => true,
            ],
        ],
        'account_email' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_config.account_email',
            'description' => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_config.account_email.description',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'readOnly' => true,
            ],
        ],
        'api_key' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_config.api_key',
            'description' => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_config.api_key.description',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'readOnly' => true,
            ],
        ],
        'site_url' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_config.site_url',
            'description' => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_config.site_url.description',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'readOnly' => true,
            ],
        ],
        'blog_storage_folder' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_config.blog_storage_folder',
            'description' => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_config.blog_storage_folder.description',
            'config' => [
                'type' => 'group',
                'allowed' => 'pages',
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 0,
            ],
        ],
        'sync_enabled' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_config.sync_enabled',
            'description' => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_config.sync_enabled.description',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => '',
                        1 => '',
                    ],
                ],
                'default' => 1,
            ],
        ],
        'render_h1_title' => [
            'exclude' => true,
            'label'   => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_config.render_h1_title',
            'description' => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_config.render_h1_title.description',
            'config' => [
                'type'       => 'check',
                'renderType' => 'checkboxToggle',
                'items'      => [
                    [
                        0 => '',
                        1 => '',
                    ],
                ],
                'default'    => 0,
            ],
        ],
        'last_sync' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_config.last_sync',
            'description' => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_config.last_sync.description',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime,int',
                'readOnly' => true,
            ],
        ],
    ],
];
