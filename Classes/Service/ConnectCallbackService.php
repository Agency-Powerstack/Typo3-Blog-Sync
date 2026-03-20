<?php

declare(strict_types=1);

namespace AgencyPowerstack\BlogSync\Service;

use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;

final class ConnectCallbackService
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly LoggerInterface $logger,
        private readonly FrontendUrlService $frontendUrlService,
    ) {
    }

    public function handleCallback(
        string $connectionId,
        string $confirmUrl,
        string $siteUrl,
        string $accountEmail = '',
    ): ResponseInterface {
        if ($connectionId === '' || $confirmUrl === '') {
            $this->logger->error('BlogSync Callback: Missing required parameters (connection_id, confirm_url)');
            return new HtmlResponse('Missing required parameters (connection_id, confirm_url).', 400);
        }

        // confirm_url must start with the configured Agency Powerstack frontend base URL.
        // This prevents an attacker from forging a callback request that sends the
        // generated API key to an arbitrary domain they control.
        $allowedBase = rtrim($this->frontendUrlService->getApplicationBaseUrl(), '/');
        if (!str_starts_with($confirmUrl, $allowedBase . '/') && $confirmUrl !== $allowedBase) {
            $this->logger->error('BlogSync Callback: confirm_url does not match allowed frontend base', [
                'confirm_url' => $confirmUrl,
                'allowed_base' => $allowedBase,
            ]);
            return new HtmlResponse('Invalid confirm_url: must start with the configured Agency Powerstack URL.', 400);
        }

        try {
            $sanitizedAccountEmail = mb_substr(strip_tags($accountEmail), 0, 255);

            $connection = $this->connectionPool->getConnectionForTable('tx_blogsync_config');
            $existing = $connection->fetchAssociative(
                'SELECT uid, api_key FROM tx_blogsync_config WHERE connection_id = ?',
                [$connectionId]
            );

            if ($existing) {
                // Reuse the existing api_key – prevents key mismatch if the callback fires twice
                // (e.g. browser double-redirect or user clicking "Connect" again for the same connection).
                $apiKey = (string) $existing['api_key'];
                $update = [
                    'tstamp' => time(),
                    'site_url' => $siteUrl,
                ];
                if ($sanitizedAccountEmail !== '') {
                    $update['account_email'] = $sanitizedAccountEmail;
                }
                $connection->update('tx_blogsync_config', $update, ['connection_id' => $connectionId]);
            } else {
                $apiKey = bin2hex(random_bytes(32));
                $connection->insert('tx_blogsync_config', [
                    'pid' => 0,
                    'tstamp' => time(),
                    'crdate' => time(),
                    'connection_id' => $connectionId,
                    'api_key' => $apiKey,
                    'account_email' => $sanitizedAccountEmail,
                    'site_url' => $siteUrl,
                    'sync_enabled' => 1,
                    'last_sync' => 0,
                    'blog_storage_folder' => 0,
                ]);
            }

            $this->logger->info('BlogSync: Account created/updated via connect callback', [
                'connection_id' => $connectionId,
                'site_url' => $siteUrl,
                'account_email' => $sanitizedAccountEmail,
            ]);

            $redirectUrl = $confirmUrl . '?' . http_build_query([
                'connection_id' => $connectionId,
                'api_key' => $apiKey,
                'typo3_api_url' => $siteUrl,
            ]);

            return new RedirectResponse($redirectUrl, 302);
        } catch (\Exception $e) {
            $this->logger->error('BlogSync Callback: Error - ' . $e->getMessage());
            return new HtmlResponse('Error processing callback. Please try again.', 500);
        }
    }
}