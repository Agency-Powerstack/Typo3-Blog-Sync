<?php

declare(strict_types=1);

namespace AgencyPowerstack\BlogSync\Controller\Api;

use AgencyPowerstack\BlogSync\Service\ApiAuthenticator;
use AgencyPowerstack\BlogSync\Service\BlogImporter;
use AgencyPowerstack\BlogSync\Service\SyncLogger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\JsonResponse;

/**
 * Receives blog posts pushed from Agency Powerstack via HTTP POST.
 *
 * Authentication: Authorization: Bearer {api_key}
 *
 * The endpoint validates the Bearer token, delegates the actual import
 * work to BlogImporter, logs the result and returns a JSON response.
 */
final class BlogPushController
{
    public function __construct(
        private readonly ApiAuthenticator $authenticator,
        private readonly ConnectionPool $connectionPool,
        private readonly BlogImporter $blogImporter,
        private readonly SyncLogger $syncLogger,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function push(ServerRequestInterface $request): ResponseInterface
    {
        // Authenticate via Bearer token
        $config = $this->authenticator->authenticate($request);
        if ($config === null) {
            return new JsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $storagePid = (int) $config['blog_storage_folder'];
        if ($storagePid === 0) {
            $this->syncLogger->log(
                (int) $config['uid'],
                'webhook',
                'error',
                0,
                0,
                'No blog storage folder configured'
            );
            return new JsonResponse(['success' => false, 'message' => 'No blog storage folder configured'], 400);
        }

        // Parse request body
        try {
            $body = json_decode((string) $request->getBody(), true, 32, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid JSON body'], 400);
        }

        if (empty($body['blog']) || !is_array($body['blog'])) {
            return new JsonResponse(['success' => false, 'message' => 'Missing or invalid blog payload'], 400);
        }

        // Defense-in-depth: verify the connection ID in the body matches the authenticated config
        $connectionId = (string) ($body['connectionId'] ?? '');
        if ($connectionId !== $config['connection_id']) {
            $this->logger->warning('BlogSync Push: connectionId mismatch', [
                'expected' => $config['connection_id'],
                'received' => $connectionId,
            ]);
            return new JsonResponse(['success' => false, 'message' => 'Connection ID mismatch'], 401);
        }

        $blogData      = $body['blog'];
        $renderH1Title = (bool) ($config['render_h1_title'] ?? false);
        $result        = $this->blogImporter->importBlog($blogData, $storagePid, $renderH1Title);

        if ($result === null) {
            $this->syncLogger->log(
                (int) $config['uid'],
                'webhook',
                'error',
                0,
                1,
                'Import failed: ' . ($blogData['title'] ?? 'Unknown')
            );
            return new JsonResponse(['success' => false, 'message' => 'Import failed'], 500);
        }

        $this->syncLogger->log(
            (int) $config['uid'],
            'webhook',
            'success',
            1,
            0,
            'Push received: ' . $result['title'],
            ['postUid' => $result['uid'], 'externalId' => $blogData['id'] ?? null]
        );

        $this->updateLastSync((int) $config['uid']);

        return new JsonResponse(['success' => true, 'postUid' => $result['uid']]);
    }

    private function updateLastSync(int $configUid): void
    {
        try {
            $this->connectionPool->getConnectionForTable('tx_blogsync_config')->update(
                'tx_blogsync_config',
                ['last_sync' => time()],
                ['uid' => $configUid]
            );
        } catch (\Exception) {
            // Non-critical
        }
    }
}
