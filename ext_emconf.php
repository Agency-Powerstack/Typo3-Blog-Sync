<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Agency Powerstack Blog Sync',
    'description' => 'Synchronize blogs from Agency Powerstack to TYPO3 Blog extension via REST API',
    'category' => 'module',
    'author' => 'Agency Powerstack',
    'author_email' => 'info@agency-powerstack.com',
    'author_company' => 'Agency Powerstack',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'version' => '1.0.4',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.99.99',
            'blog' => '*',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
