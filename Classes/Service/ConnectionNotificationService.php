<?php

declare(strict_types=1);

namespace AgencyPowerstack\BlogSync\Service;

use Psr\Log\LoggerInterface;

final class ConnectionNotificationService
{
    public function __construct(
        private readonly FrontendUrlService $frontendUrlService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function notifyBackendConnectionDeleted(string $connectionId): void
    {
        if ($connectionId === '') {
            return;
        }

        $apiUrl = rtrim($this->frontendUrlService->getApplicationBaseUrl(), '/')
            . '/backend/typo3/connections/' . urlencode($connectionId);

        $context = stream_context_create([
            'http' => [
                'method'        => 'DELETE',
                'timeout'       => 10,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        @file_get_contents($apiUrl, false, $context);

        $this->logger->info('BlogSync: Backend notified about account deletion', [
            'connection_id' => $connectionId,
        ]);
    }
}