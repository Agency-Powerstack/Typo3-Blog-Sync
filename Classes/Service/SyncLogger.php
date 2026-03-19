<?php

declare(strict_types=1);

namespace AgencyPowerstack\BlogSync\Service;

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Persists sync operation results to the tx_blogsync_log table.
 */
final class SyncLogger
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param int                  $configId      FK to tx_blogsync_config.uid
     * @param string               $syncType      'webhook' | 'cron' | 'manual'
     * @param string               $status        'success' | 'error'
     * @param int                  $importedCount Number of successfully imported posts
     * @param int                  $failedCount   Number of failed imports
     * @param string               $message       Human-readable summary
     * @param array<string, mixed> $details       Optional structured metadata (stored as JSON)
     */
    public function log(
        int $configId,
        string $syncType,
        string $status,
        int $importedCount,
        int $failedCount,
        string $message,
        array $details = [],
    ): void {
        try {
            $this->connectionPool->getConnectionForTable('tx_blogsync_log')->insert('tx_blogsync_log', [
                'pid'            => 0,
                'tstamp'         => time(),
                'crdate'         => time(),
                'config_id'      => $configId,
                'sync_type'      => $syncType,
                'status'         => $status,
                'imported_count' => $importedCount,
                'failed_count'   => $failedCount,
                'message'        => $message,
                'details'        => $details !== [] ? json_encode($details, \JSON_UNESCAPED_UNICODE) : '',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('BlogSync: Failed to write sync log – ' . $e->getMessage());
        }
    }
}
