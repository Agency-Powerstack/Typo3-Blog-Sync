<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_log',
        'label' => 'sync_type',
        'label_alt' => 'status,tstamp',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'searchFields' => 'sync_type,status,message',
        'iconfile' => 'EXT:blog_sync/Resources/Public/Icons/Extension.svg',
        'readOnly' => 1, // Not creatable, not editable
        'hideTable' => false,
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    sync_type, status, imported_count, failed_count, message, details, tstamp
            ',
        ],
    ],
    'columns' => [
        'pid' => [
            'label' => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_log.pid',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_blogsync_config',
                'size' => 1,
                'maxitems' => 1,
                'readOnly' => true,
            ],
        ],
        'sync_type' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_log.sync_type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_log.sync_type.webhook', 'webhook'],
                    ['LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_log.sync_type.cron', 'cron'],
                    ['LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_log.sync_type.manual', 'manual'],
                ],
                'readOnly' => true,
            ],
        ],
        'status' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_log.status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_log.status.success', 'success'],
                    ['LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_log.status.error', 'error'],
                ],
                'readOnly' => true,
            ],
        ],
        'imported_count' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_log.imported_count',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'readOnly' => true,
            ],
        ],
        'failed_count' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_log.failed_count',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'readOnly' => true,
            ],
        ],
        'message' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_log.message',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 50,
                'readOnly' => true,
            ],
        ],
        'details' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blogsync_log.details',
            'config' => [
                'type' => 'text',
                'rows' => 10,
                'cols' => 50,
                'readOnly' => true,
            ],
        ],
    ],
];
