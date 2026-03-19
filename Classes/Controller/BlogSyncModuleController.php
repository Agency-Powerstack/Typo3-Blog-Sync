<?php

declare(strict_types=1);

namespace AgencyPowerstack\BlogSync\Controller;

use AgencyPowerstack\BlogSync\Service\ConnectionNotificationService;
use AgencyPowerstack\BlogSync\Service\FrontendUrlService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\RedirectResponse;

/**
 * Backend module controller for Agency Powerstack Blog Sync.
 *
 * Intentionally NOT extending Extbase ActionController: Extbase backend modules
 * call BackendConfigurationManager::getTypoScriptSetup(), which resolves the
 * rootline for the selected page ID. If that page does not exist the request
 * crashes with a PageNotFoundException — a brittle dependency for a module that
 * has no page-context requirements whatsoever.
 *
 * Using a plain PSR-7 handler avoids this entirely and is the TYPO3 14
 * recommended approach for backend-only modules.
 */
final class BlogSyncModuleController
{
    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly ConnectionPool $connectionPool,
        private readonly FrontendUrlService $frontendUrlService,
        private readonly ConnectionNotificationService $connectionNotificationService,
        private readonly BackendUriBuilder $backendUriBuilder,
    ) {
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        return match ($request->getQueryParams()['action'] ?? 'index') {
            'delete' => $this->deleteAction($request),
            default  => $this->indexAction($request),
        };
    }

    private function indexAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);

        $frontendUrl = $this->frontendUrlService->getFrontendUrl();

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_blogsync_config');
        $connections = $queryBuilder
            ->select('*')
            ->from('tx_blogsync_config')
            ->where(
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->executeQuery()
            ->fetchAllAssociative();

        $siteUrl      = rtrim((string) $request->getAttribute('normalizedParams')->getSiteUrl(), '/');
        $callbackUrl  = $siteUrl . '/typo3/blog-sync/callback';
        $returnUrl    = (string) $this->backendUriBuilder->buildUriFromRoute('web_blogsync');
        $connections  = $this->enrichConnections($connections, $returnUrl);

        $hasUnconfiguredConnections = array_reduce(
            $connections,
            static fn (bool $carry, array $c): bool => $carry || $c['storage_folder_label'] === null,
            false
        );

        $connectUrl = rtrim($frontendUrl, '/') . '/connect/typo3?callback_url='
            . urlencode($callbackUrl)
            . '&site_url=' . urlencode($siteUrl)
            . '&admin_return_url=' . urlencode($returnUrl);

        $moduleTemplate->assignMultiple([
            'connections'                => $connections,
            'hasUnconfiguredConnections' => $hasUnconfiguredConnections,
            'connectUrl'                 => $connectUrl,
            'frontendUrl'                => $frontendUrl,
        ]);

        return $moduleTemplate->renderResponse('Backend/Module/Index');
    }

    private function deleteAction(ServerRequestInterface $request): ResponseInterface
    {
        $uid = (int) ($request->getQueryParams()['uid'] ?? 0);

        if ($uid > 0) {
            $connection = $this->connectionPool->getConnectionForTable('tx_blogsync_config');
            $record = $connection->fetchAssociative(
                'SELECT uid, connection_id FROM tx_blogsync_config WHERE uid = ? AND deleted = 0',
                [$uid]
            );

            if ($record && !empty($record['connection_id'])) {
                $this->connectionNotificationService->notifyBackendConnectionDeleted((string) $record['connection_id']);
                $connection->update('tx_blogsync_config', [
                    'deleted' => 1,
                    'tstamp'  => time(),
                ], ['uid' => $uid]);
            }
        }

        return new RedirectResponse(
            (string) $this->backendUriBuilder->buildUriFromRoute('web_blogsync')
        );
    }

    /**
     * @param array<int, array<string, mixed>> $connections
     * @return array<int, array<string, mixed>>
     */
    private function enrichConnections(array $connections, string $returnUrl): array
    {
        return array_map(function (array $connection) use ($returnUrl): array {
            $connection['editUrl'] = (string) $this->backendUriBuilder->buildUriFromRoute('record_edit', [
                'edit' => [
                    'tx_blogsync_config' => [
                        (int) ($connection['uid'] ?? 0) => 'edit',
                    ],
                ],
                'returnUrl' => $returnUrl,
            ]);

            $connection['deleteUrl'] = (string) $this->backendUriBuilder->buildUriFromRoute('web_blogsync', [
                'action' => 'delete',
                'uid'    => (int) ($connection['uid'] ?? 0),
            ]);

            $storagePid = (int) ($connection['blog_storage_folder'] ?? 0);
            if ($storagePid > 0) {
                try {
                    $page = $this->connectionPool->getConnectionForTable('pages')->fetchAssociative(
                        'SELECT title FROM pages WHERE uid = ? AND deleted = 0',
                        [$storagePid]
                    );
                    $connection['storage_folder_label'] = $page
                        ? $page['title'] . ' [' . $storagePid . ']'
                        : '[' . $storagePid . ']';
                } catch (\Throwable) {
                    $connection['storage_folder_label'] = '[' . $storagePid . ']';
                }
            } else {
                $connection['storage_folder_label'] = null;
            }

            return $connection;
        }, $connections);
    }
}
