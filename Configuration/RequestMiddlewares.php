<?php

declare(strict_types=1);

return [
    'backend' => [
        'typo3/cms-backend/authentication' => [
            'target' => \AgencyPowerstack\BlogSync\Middleware\PublicCallbackBackendUserAuthenticator::class,
            'after' => [
                'typo3/cms-backend/backend-routing',
            ],
        ],
    ],
];
