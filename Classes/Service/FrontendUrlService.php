<?php

declare(strict_types=1);

namespace AgencyPowerstack\BlogSync\Service;

/**
 * Provides the Agency Powerstack frontend URL for the OAuth-like connect flow.
 *
 * The frontend URL is determined automatically based on the environment:
 * - Development: http://localhost:3081
 * - Production: https://app.agency-powerstack.com
 *
 * This approach matches the Contao Blog Sync plugin and ensures consistency
 * across all CMS integrations.
 */
final class FrontendUrlService
{
    public function getApplicationBaseUrl(): string
    {
        if ($this->isDevelopmentEnvironment()) {
            return 'http://localhost:3081';
        }

        return 'https://app.agency-powerstack.com';
    }

    /**
     * Returns the Agency Powerstack frontend URL for the current environment.
     *
     * Development is detected by checking if the current request originates from
     * localhost or a local IP address, or by checking the APPLICATION_CONTEXT.
     *
     * @return string The frontend URL (without trailing slash) including locale prefix
     */
    public function getFrontendUrl(): string
    {
        return $this->getApplicationBaseUrl() . '/de';
    }

    /**
     * Checks if the current environment is development.
     *
     * @return bool True if in development, false otherwise
     */
    private function isDevelopmentEnvironment(): bool
    {
        // Check APPLICATION_CONTEXT (e.g., "Development", "Development/Docker")
        $context = getenv('APPLICATION_CONTEXT') ?: (\TYPO3\CMS\Core\Core\Environment::getContext()->isDevelopment() ? 'Development' : 'Production');
        if (stripos($context, 'Development') !== false) {
            return true;
        }

        // Check if SERVER_NAME indicates localhost
        $serverName = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? '';
        if (preg_match('/^(localhost|127\.0\.0\.1|::1)$/i', $serverName)) {
            return true;
        }

        return false;
    }
}
