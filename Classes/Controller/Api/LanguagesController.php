<?php

declare(strict_types=1);

namespace AgencyPowerstack\BlogSync\Controller\Api;

use AgencyPowerstack\BlogSync\Service\ApiAuthenticator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Returns the configured site languages for this TYPO3 installation.
 *
 * Called by Agency Powerstack during the connection confirm step to
 * automatically determine the target language for blog post translation.
 */
final class LanguagesController
{
    public function __construct(
        private readonly ApiAuthenticator $authenticator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function languages(ServerRequestInterface $request): ResponseInterface
    {
        $config = $this->authenticator->authenticate($request, requireEnabled: false);
        if ($config === null) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        try {
            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            $languages  = [];

            foreach ($siteFinder->getAllSites() as $site) {
                foreach ($site->getAllLanguages() as $lang) {
                    $languages[] = [
                        'languageId'       => $lang->getLanguageId(),
                        'title'            => $lang->getTitle(),
                        'twoLetterIsoCode' => $lang->getLocale()->getLanguageCode(),
                        'locale'           => $lang->getLocale()->getName(),
                        'isDefault'        => $lang->getLanguageId() === 0,
                    ];
                }
            }

            return new JsonResponse(['languages' => $languages]);

        } catch (\Throwable $e) {
            $this->logger->error('BlogSync LanguagesController: Error – ' . $e->getMessage());
            return new JsonResponse(['error' => 'Could not retrieve languages'], 500);
        }
    }
}
