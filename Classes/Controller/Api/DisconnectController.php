<?php

declare(strict_types=1);

namespace AgencyPowerstack\BlogSync\Controller\Api;

use AgencyPowerstack\BlogSync\Service\ApiAuthenticator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\JsonResponse;

/**
 * Handles disconnect requests from Agency Powerstack.
 *
 * Removes the matching tx_blogsync_config record identified by the Bearer token.
 * Authentication is performed without requiring sync_enabled=1 so that disabled
 * connections can still be disconnected cleanly.
 */
final class DisconnectController
{
    public function __construct(
        private readonly ApiAuthenticator $authenticator,
        private readonly ConnectionPool $connectionPool,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function disconnect(ServerRequestInterface $request): ResponseInterface
    {
        // Allow disconnecting even if sync is disabled
        $config = $this->authenticator->authenticate($request, requireEnabled: false);
        if ($config === null) {
            return new JsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $this->connectionPool->getConnectionForTable('tx_blogsync_config')->delete('tx_blogsync_config', ['uid' => (int) $config['uid']]);

            $this->logger->info('BlogSync: Connection disconnected', [
                'connection_id' => $config['connection_id'],
            ]);

            return new JsonResponse(['success' => true]);

        } catch (\Exception $e) {
            $this->logger->error('BlogSync Disconnect: Error – ' . $e->getMessage());
            return new JsonResponse(['success' => false, 'message' => 'Disconnect failed'], 500);
        }
    }
}
