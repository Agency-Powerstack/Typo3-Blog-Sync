<?php

defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

// Add external_id field to tx_blog_post
$tempColumns = [
    'external_id' => [
        'exclude' => true,
        'label' => 'LLL:EXT:blog_sync/Resources/Private/Language/locallang_db.xlf:tx_blog_post.external_id',
        'config' => [
            'type' => 'input',
            'size' => 50,
            'max' => 255,
            'readOnly' => true,
        ],
    ],
];

ExtensionManagementUtility::addTCAcolumns('tx_blog_post', $tempColumns);
ExtensionManagementUtility::addToAllTCAtypes(
    'tx_blog_post',
    'external_id',
    '',
    'after:uid'
);
