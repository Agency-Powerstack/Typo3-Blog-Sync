<?php

declare(strict_types=1);

namespace AgencyPowerstack\BlogSync\Controller;

use AgencyPowerstack\BlogSync\Service\ConnectCallbackService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Handles the OAuth-like callback from the Agency Powerstack frontend during the connect flow.
 *
 * Flow:
 *  1. The user clicks "Connect" in the TYPO3 backend module.
 *  2. TYPO3 redirects to {frontendUrl}/connect/typo3 with this controller's URL as callback_url.
 *  3. The frontend calls the backend, obtains a connection_id and confirm_url, then redirects
 *     the user back here.
 *  4. This controller generates a secure API key, persists the connection and redirects the user
 *     to confirm_url so the frontend can store the api_key and typo3_api_url.
 *
 * Security: confirm_url must use HTTPS (or http://localhost for local development).
 */
final class ConnectCallbackController
{
    public function __construct(
        private readonly ConnectCallbackService $connectCallbackService,
    ) {
    }

    public function callback(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams  = $request->getQueryParams();
        $connectionId = (string) ($queryParams['connection_id'] ?? '');
        $confirmUrl   = (string) ($queryParams['confirm_url'] ?? '');
        $forwardedProto = $request->getHeaderLine('X-Forwarded-Proto');
        $scheme         = $forwardedProto !== '' ? $forwardedProto : $request->getUri()->getScheme();
        $host           = $request->getUri()->getHost();
        $port           = $request->getUri()->getPort();
        $siteUrl        = $scheme . '://' . $host . ($port ? ':' . $port : '');

        return $this->connectCallbackService->handleCallback(
            $connectionId,
            $confirmUrl,
            $siteUrl,
            (string) ($queryParams['account_email'] ?? '')
        );
    }
}
