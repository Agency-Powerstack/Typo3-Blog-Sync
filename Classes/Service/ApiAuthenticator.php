<?php

declare(strict_types=1);

namespace AgencyPowerstack\BlogSync\Service;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Validates Bearer token API keys for incoming push and disconnect requests.
 *
 * Fetches all enabled configurations from the database and performs a
 * constant-time comparison (hash_equals) to prevent timing attacks.
 */
final class ApiAuthenticator
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Authenticates an incoming request via Bearer token.
     *
     * @param ServerRequestInterface $request        The incoming HTTP request.
     * @param bool                   $requireEnabled When true (default), only configs with sync_enabled=1 are checked.
     *
     * @return array{uid: int, api_key: string, connection_id: string, blog_storage_folder: int, sync_enabled: int, render_h1_title: int}|null
     */
    public function authenticate(ServerRequestInterface $request, bool $requireEnabled = true): ?array
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if ($authHeader === '') {
            // Fallback: Apache mod_rewrite may place the header in REDIRECT_HTTP_AUTHORIZATION
            // instead of HTTP_AUTHORIZATION, which the PSR-7 factory does not map to headers.
            $serverParams = $request->getServerParams();
            $authHeader = $serverParams['REDIRECT_HTTP_AUTHORIZATION']
                ?? $serverParams['HTTP_AUTHORIZATION']
                ?? '';
        }

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $providedKey = substr($authHeader, 7);
        if ($providedKey === '') {
            return null;
        }

        $where = "api_key != '' AND deleted = 0";
        if ($requireEnabled) {
            $where .= ' AND sync_enabled = 1';
        }

        try {
            $connection = $this->connectionPool->getConnectionForTable('tx_blogsync_config');
            $configs = $connection->fetchAllAssociative(
                "SELECT uid, api_key, connection_id, blog_storage_folder, sync_enabled, render_h1_title
                 FROM tx_blogsync_config
                 WHERE {$where}"
            );
        } catch (\Exception $e) {
            $this->logger->error('BlogSync: DB error during authentication – ' . $e->getMessage());
            return null;
        }

        foreach ($configs as $config) {
            if (hash_equals($config['api_key'], $providedKey)) {
                return $config;
            }
        }

        return null;
    }
}
