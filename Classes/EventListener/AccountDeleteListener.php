<?php

declare(strict_types=1);

namespace AgencyPowerstack\BlogSync\EventListener;

use AgencyPowerstack\BlogSync\Service\ConnectionNotificationService;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Notifies the Agency Powerstack backend when a blog sync config record is deleted.
 *
 * Registered as a processCmdmapClass hook in ext_localconf.php.
 * The deletion itself is performed by TYPO3 DataHandler; this listener only
 * sends a best-effort HTTP DELETE notification so the backend can clean up
 * the corresponding connection record on its side.
 * If the notification fails, the local deletion still proceeds.
 */
final class AccountDeleteListener
{
    public function __construct(
        private readonly ?ConnectionPool $connectionPool = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?ConnectionNotificationService $connectionNotificationService = null,
    ) {
    }

    /**
     * Called by TYPO3 DataHandler before a record is deleted.
     *
     * @param string      $table        The database table name
     * @param int|string  $id           The record UID
     * @param array       $record       The record data
     * @param bool        $recordWasDeleted Whether the record was actually deleted
     * @param DataHandler $dataHandler  The DataHandler instance
     */
    public function processCmdmap_deleteAction(
        string $table,
        int|string $id,
        array $record,
        bool &$recordWasDeleted,
        DataHandler $dataHandler
    ): void {
        if ($table !== 'tx_blogsync_config') {
            return;
        }

        $connectionPool = $this->connectionPool ?? GeneralUtility::makeInstance(ConnectionPool::class);
        $logger = $this->logger ?? GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        $connectionNotificationService = $this->connectionNotificationService
            ?? GeneralUtility::makeInstance(ConnectionNotificationService::class);

        try {
            $connection = $connectionPool->getConnectionForTable('tx_blogsync_config');
            $config = $connection->fetchAssociative(
                'SELECT connection_id FROM tx_blogsync_config WHERE uid = ?',
                [(int) $id]
            );

            if (!$config || empty($config['connection_id'])) {
                return;
            }

            $connectionNotificationService->notifyBackendConnectionDeleted((string) $config['connection_id']);

        } catch (\Exception $e) {
            // Network errors must not prevent the local deletion
            $logger->error('BlogSync: Error notifying backend about deletion - ' . $e->getMessage());
        }
    }
}
