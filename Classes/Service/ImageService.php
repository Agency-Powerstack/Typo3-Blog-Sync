<?php

declare(strict_types=1);

namespace AgencyPowerstack\BlogSync\Service;

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Downloads remote images and registers them in the TYPO3 File Abstraction Layer (FAL).
 *
 * Images are stored under fileadmin/blog-images/{safePostId}/
 * and registered in sys_file via the default storage.
 *
 * SVG is intentionally excluded because SVG files may contain embedded
 * JavaScript and can be rendered directly by the browser, creating an XSS vector.
 */
final class ImageService
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Ensures a remote image exists locally and is registered in FAL.
     *
     * @param string $imageUrl   Full URL of the remote image
     * @param string $safePostId Sanitized post ID used as subdirectory name
     * @param string $suffix     Filename suffix (e.g. 'featured', 'content-0')
     *
     * @return int|null  FAL file UID, or null on failure
     */
    public function ensureImageRegistered(string $imageUrl, string $safePostId, string $suffix, string $originalFilename = ''): ?int
    {
        try {
            if ($originalFilename !== '') {
                $basename  = preg_replace('/[^a-zA-Z0-9_-]/', '-', pathinfo($originalFilename, PATHINFO_FILENAME)) ?: $suffix;
                $ext       = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
                $extension = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'], true) ? $ext : $this->guessExtension($imageUrl);
            } else {
                $basename  = $this->guessFilename($imageUrl, $suffix);
                $extension = $this->guessExtension($imageUrl);
            }
            $filename = $basename . '.' . $extension;

            $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
            $storage           = $storageRepository->getDefaultStorage();
            if ($storage === null) {
                $this->logger->error('BlogSync ImageService: No default FAL storage found');
                return null;
            }

            // Disable user-based permission checks: the push webhook runs without an
            // authenticated backend user, so FAL would otherwise deny folder access.
            $storage->setEvaluatePermissions(false);

            $folderIdentifier = 'blog-images/' . $safePostId;

            // Create target folder if it does not exist
            if (!$storage->hasFolder($folderIdentifier)) {
                $storage->createFolder($folderIdentifier);
            }
            $folder = $storage->getFolder($folderIdentifier);

            // Return existing file UID without re-downloading
            if ($storage->hasFileInFolder($filename, $folder)) {
                $existingFile = $storage->getFileInFolder($filename, $folder);
                $this->logger->info("BlogSync: Image already exists in FAL: {$folderIdentifier}/{$filename}");
                return $existingFile->getUid();
            }

            // Download and validate
            $imageContent = $this->downloadFile($imageUrl);
            if ($imageContent === null) {
                return null;
            }

            // Write atomically to temp file, then add to FAL storage
            $tmpPath = Environment::getVarPath() . '/blogsync_' . bin2hex(random_bytes(8)) . '.' . $extension;
            if (file_put_contents($tmpPath, $imageContent) === false) {
                $this->logger->error("BlogSync: Could not write temp file: {$tmpPath}");
                return null;
            }

            try {
                $file = $storage->addFile($tmpPath, $folder, $filename);
                $this->logger->info("BlogSync: Image registered in FAL: {$folderIdentifier}/{$filename}");
                return $file->getUid();
            } finally {
                if (file_exists($tmpPath)) {
                    unlink($tmpPath);
                }
            }

        } catch (\Throwable $e) {
            $this->logger->error('BlogSync ImageService: Error registering image – ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Downloads and validates a remote image file.
     *
     * @return string|null  Raw image bytes, or null on failure
     */
    private function downloadFile(string $url): ?string
    {
        $this->logger->debug('BlogSync: Downloading image from: ' . substr($url, 0, 100));

        $context = stream_context_create([
            'http' => [
                'timeout'         => 30,
                'user_agent'      => 'TYPO3BlogSync/1.0',
                'ignore_errors'   => true,
                'follow_location' => 1,
                'max_redirects'   => 5,
            ],
            'ssl'  => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $content    = @file_get_contents($url, false, $context);
        $httpStatus = $http_response_header[0] ?? 'n/a';

        if (!is_string($content) || $content === '') {
            $this->logger->warning('BlogSync: Image download returned empty – likely a network error');
            return null;
        }

        if (!empty($http_response_header) && !preg_match('#HTTP/[\d.]+\s+2\d\d#', $httpStatus)) {
            $this->logger->warning("BlogSync: Image download failed – HTTP: {$httpStatus}");
            return null;
        }

        if (!$this->isValidImageContent($content)) {
            $this->logger->warning(sprintf(
                'BlogSync: Downloaded content is not a valid image (%d bytes)',
                strlen($content)
            ));
            return null;
        }

        return $content;
    }

    /**
     * Magic-byte validation for supported image formats.
     * SVG is intentionally excluded (XSS risk).
     */
    private function isValidImageContent(string $content): bool
    {
        if (strlen($content) < 8) {
            return false;
        }

        return str_starts_with($content, "\xFF\xD8\xFF")       // JPEG
            || str_starts_with($content, "\x89PNG\r\n\x1a\n")  // PNG
            || str_starts_with($content, 'GIF8')               // GIF
            || str_starts_with($content, 'RIFF')               // WebP
            || substr($content, 4, 4) === 'ftyp';              // AVIF / HEIC
    }

    private function guessFilename(string $url, string $fallback): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if ($path) {
            $name = pathinfo($path, PATHINFO_FILENAME);
            if ($name !== '' && strlen($name) < 100 && !preg_match('/^[a-f0-9\-]{32,}$/i', $name)) {
                return preg_replace('/[^a-zA-Z0-9_-]/', '-', $name);
            }
        }
        return $fallback;
    }

    private function guessExtension(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if ($path) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'], true)) {
                return $ext;
            }
        }
        return 'jpg';
    }
}
