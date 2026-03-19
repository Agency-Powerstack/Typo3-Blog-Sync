<?php

declare(strict_types=1);

namespace AgencyPowerstack\BlogSync\Service;

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Imports or updates a blog post pushed from Agency Powerstack.
 *
 * EXT:t3g/blog stores posts as `pages` records with doktype=137.
 * The post body is split at {{typo3_image::N}} placeholders into alternating
 * tt_content elements: CType='html' for HTML segments, CType='image' for images.
 *
 * Responsibilities:
 *  - Create or update a pages record (doktype=137) identified by external_id
 *  - Delete and recreate all tt_content records for the page on each import
 *  - Download and register the featured image and content images via TYPO3 FAL
 *  - Create native image content elements instead of embedding images in HTML
 */
final class BlogImporter
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly ImageService $imageService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Imports a single blog post into the given storage folder (page).
     *
     * @param array<string, mixed> $blogData    Validated push payload from the backend.
     * @param int                  $storagePid  PID of the TYPO3 page/folder for blog posts.
     *
     * @return array{uid: int, title: string}|null  The saved post data, or null if the import failed.
     */
    public function importBlog(array $blogData, int $storagePid, bool $renderH1Title = false): ?array
    {
        try {
            $externalId = (string) ($blogData['id'] ?? '');
            if ($externalId === '') {
                $this->logger->error('BlogSync: Blog has no ID, skipping');
                return null;
            }

            // Sanitize external ID for filesystem use (prevents path traversal)
            $safePostId = preg_replace('/[^a-zA-Z0-9_-]/', '-', $externalId);

            $pagesConn = $this->connectionPool->getConnectionForTable('pages');

            // Check if already imported (identified by external_id on pages)
            $existing = $pagesConn->fetchAssociative(
                'SELECT uid FROM pages WHERE external_id = ? AND deleted = 0 AND doktype = 137',
                [$externalId]
            );
            $isUpdate = ($existing !== false && $existing !== null);

            $title            = $blogData['title'] ?? 'Untitled';
            $rawHtml          = $blogData['targetSystemCode'] ?? $blogData['htmlContent'] ?? '';
            $abstract         = $this->buildAbstract($blogData['textContent'] ?? '', $rawHtml);
            $isPublished      = (($blogData['status'] ?? '') === 'PUBLISH');
            $createdAt        = !empty($blogData['createdAt']) ? strtotime($blogData['createdAt']) : time();
            $contentImageUrls = $blogData['contentImageUrls'] ?? [];
            $contentImages    = $blogData['contentImages'] ?? null;
            $postImageMeta    = $blogData['postImageMeta'] ?? null;
            $now              = time();

            if ($isUpdate) {
                $pageUid = (int) $existing['uid'];
                $this->logger->info("BlogSync: Updating existing post {$externalId} (uid={$pageUid})");

                $pagesConn->update('pages', [
                    'tstamp'       => $now,
                    'title'        => $title,
                    'abstract'     => $abstract,
                    'hidden'       => $isPublished ? 0 : 1,
                    'publish_date' => $createdAt,
                ], ['uid' => $pageUid]);
            } else {
                $slug = $this->generateSlug($title);

                $pagesConn->insert('pages', [
                    'pid'          => $storagePid,
                    'tstamp'       => $now,
                    'crdate'       => $now,
                    'hidden'       => $isPublished ? 0 : 1,
                    'deleted'      => 0,
                    'doktype'      => 137,
                    'title'        => $title,
                    'abstract'     => $abstract,
                    'slug'         => $slug,
                    'publish_date' => $createdAt,
                    'external_id'  => $externalId,
                    'media'        => 0,
                ]);

                $pageUid = (int) $pagesConn->lastInsertId();
            }

            // Update SEO description if provided (non-fatal – field may not exist in all TYPO3 setups)
            if (!empty($blogData['metaDescription'])) {
                try {
                    $pagesConn->update('pages', [
                        'description' => mb_substr($blogData['metaDescription'], 0, 255),
                    ], ['uid' => $pageUid]);
                } catch (\Throwable $e) {
                    $this->logger->warning('BlogSync: Could not update page description – ' . $e->getMessage());
                }
            }

            $imageSlug = $this->buildImageSlug($title);

            // Rebuild all tt_content elements (text segments + image elements)
            $this->updateContentElements($rawHtml, $pageUid, $storagePid, $imageSlug, $contentImageUrls, $contentImages, $title, $now, $renderH1Title);

            // Handle featured image (sys_file_reference on pages.media)
            if (!empty($blogData['postImageUrl'])) {
                $this->handleFeaturedImage(
                    $blogData['postImageUrl'],
                    $safePostId,
                    $pageUid,
                    $storagePid,
                    $isUpdate,
                    $imageSlug,
                    $postImageMeta['filename'] ?? '',
                    $postImageMeta['altText'] ?? $title
                );
            }

            $this->logger->info("BlogSync: Successfully imported post: {$title} (uid={$pageUid})");

            return ['uid' => $pageUid, 'title' => $title];

        } catch (\Throwable $e) {
            $this->logger->error('BlogSync: Error importing blog – ' . $e->getMessage());
            return null;
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Deletes all existing tt_content records for the page and recreates them by
     * splitting the HTML at {{typo3_image::N}} placeholders into alternating
     * CType='html' and CType='image' content elements.
     *
     * @param array<int|string, string> $contentImageUrls  Map of placeholder index → remote image URL.
     */
    private function updateContentElements(
        string $rawHtml,
        int $pageUid,
        int $storagePid,
        string $imageSlug,
        array $contentImageUrls,
        ?array $contentImages,
        string $postTitle,
        int $now,
        bool $renderH1Title = false
    ): void {
        try {
            $ttConn = $this->connectionPool->getConnectionForTable('tt_content');

            // Remove all previous content elements for this page
            $ttConn->delete('tt_content', ['pid' => $pageUid, 'deleted' => 0]);

            if (trim($rawHtml) === '') {
                return;
            }

            // Insert H1 title as the very first content element (optional, controlled per connection)
            if ($renderH1Title) {
                $ttConn->insert('tt_content', [
                    'pid'      => $pageUid,
                    'tstamp'   => $now,
                    'crdate'   => $now,
                    'hidden'   => 0,
                    'deleted'  => 0,
                    'CType'    => 'html',
                    'bodytext' => '<h1>' . htmlspecialchars($postTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h1>',
                    'colPos'   => 0,
                    'sorting'  => 64,
                ]);
            }

            $html    = $this->normalizeImagePlaceholders($rawHtml);
            $parts   = preg_split('/\{\{typo3_image::(\d+)\}\}/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
            $sorting = 128;
            $i       = 0;
            $count   = count($parts);

            while ($i < $count) {
                // Even token: HTML segment
                $segment = $parts[$i];
                if (trim($segment) !== '') {
                    $ttConn->insert('tt_content', [
                        'pid'      => $pageUid,
                        'tstamp'   => $now,
                        'crdate'   => $now,
                        'hidden'   => 0,
                        'deleted'  => 0,
                        'CType'    => 'html',
                        'bodytext' => $segment,
                        'colPos'   => 0,
                        'sorting'  => $sorting,
                    ]);
                    $sorting += 128;
                }
                $i++;

                // Odd token: image placeholder index
                if ($i < $count) {
                    $index    = (int) $parts[$i];
                    $imgMeta  = $contentImages[$index] ?? null;
                    $url      = $imgMeta['url'] ?? $contentImageUrls[$index] ?? null;
                    $altText  = !empty($imgMeta['altText']) ? $imgMeta['altText'] : $postTitle;
                    $origFile = $imgMeta['filename'] ?? '';

                    if ($url !== null && $url !== '') {
                        $fileUid = $this->imageService->ensureImageRegistered(
                            $url,
                            $imageSlug,
                            $imageSlug . '-' . ($index + 1),
                            $origFile
                        );

                        if ($fileUid !== null) {
                            $ttConn->insert('tt_content', [
                                'pid'     => $pageUid,
                                'tstamp'  => $now,
                                'crdate'  => $now,
                                'hidden'  => 0,
                                'deleted' => 0,
                                'CType'   => 'image',
                                'image'   => 1,
                                'colPos'  => 0,
                                'sorting' => $sorting,
                            ]);
                            $ttContentUid = (int) $ttConn->lastInsertId();
                            $sorting += 128;

                            $this->connectionPool->getConnectionForTable('sys_file_reference')->insert(
                                'sys_file_reference',
                                [
                                    'tstamp'          => $now,
                                    'crdate'          => $now,
                                    'uid_local'       => $fileUid,
                                    'uid_foreign'     => $ttContentUid,
                                    'tablenames'      => 'tt_content',
                                    'fieldname'       => 'image',
                                    'pid'             => $pageUid,
                                    'sorting_foreign' => 1,
                                    'hidden'          => 0,
                                    'deleted'         => 0,
                                    'alternative'     => $altText,
                                    'title'           => $altText,
                                ]
                            );
                        }
                    }
                    $i++;
                }
            }

            $this->logger->info("BlogSync: tt_content elements created for page uid={$pageUid}");

        } catch (\Throwable $e) {
            $this->logger->error("BlogSync: updateContentElements failed for page uid={$pageUid} – " . $e->getMessage());
        }
    }

    /**
     * Normalises {{typo3_image::N}} placeholders in AI-generated HTML.
     *
     * The AI wraps placeholders inside <img src="{{...}}"> and optional <figure>/<p>
     * elements. This method extracts bare placeholders so preg_split() produces
     * clean HTML segments without broken tags.
     *
     * Steps:
     *  1. <img src="{{typo3_image::N}}" ...> → {{typo3_image::N}}
     *  2. <figure>...(placeholder + optional figcaption)...</figure> → {{typo3_image::N}}
     *  3. <p>...(placeholder only)...</p> → {{typo3_image::N}}
     */
    private function normalizeImagePlaceholders(string $html): string
    {
        // 1. Extract placeholder from <img src="...">
        $html = preg_replace(
            '/<img\b[^>]*\bsrc="(\{\{typo3_image::\d+\}\})"[^>]*\/?>/i',
            '$1',
            $html
        );

        // 2. Strip <figure> wrapper (with optional figcaption)
        $html = preg_replace(
            '/<figure\b[^>]*>\s*(\{\{typo3_image::\d+\}\})\s*(?:<figcaption\b[^>]*>.*?<\/figcaption>\s*)?<\/figure>/si',
            '$1',
            $html
        );

        // 3. Strip <p> wrapper (when placeholder is the sole content)
        $html = preg_replace(
            '/<p\b[^>]*>\s*(\{\{typo3_image::\d+\}\})\s*<\/p>/si',
            '$1',
            $html
        );

        return $html ?? '';
    }

    /**
     * Builds the abstract (teaser) text, max 300 characters.
     */
    private function buildAbstract(string $textContent, string $htmlFallback): string
    {
        $text = trim($textContent);
        if ($text === '') {
            $text = trim(strip_tags($htmlFallback));
        }
        if (mb_strlen($text) > 300) {
            $text = mb_substr($text, 0, 297) . '...';
        }
        return $text;
    }

    /**
     * Downloads the featured image and creates a sys_file_reference linking it to the pages record.
     */
    private function handleFeaturedImage(
        string $imageUrl,
        string $safePostId,
        int $pageUid,
        int $storagePid,
        bool $isUpdate,
        string $imageSlug = '',
        string $filename = '',
        string $altText = ''
    ): void {
        $featuredSuffix = $imageSlug !== '' ? $imageSlug . '-featured' : 'featured';
        $fileUid = $this->imageService->ensureImageRegistered($imageUrl, $safePostId, $featuredSuffix, $filename);
        if ($fileUid === null) {
            return;
        }

        $fileRefConn = $this->connectionPool->getConnectionForTable('sys_file_reference');

        if ($isUpdate) {
            $fileRefConn->delete('sys_file_reference', [
                'uid_foreign' => $pageUid,
                'tablenames'  => 'pages',
                'fieldname'   => 'media',
                'deleted'     => 0,
            ]);
        }

        $now = time();
        $fileRefConn->insert('sys_file_reference', [
            'tstamp'          => $now,
            'crdate'          => $now,
            'uid_local'       => $fileUid,
            'uid_foreign'     => $pageUid,
            'tablenames'      => 'pages',
            'fieldname'       => 'media',
            'pid'             => $storagePid,
            'sorting_foreign' => 1,
            'hidden'          => 0,
            'deleted'         => 0,
            'alternative'     => $altText,
            'title'           => $altText,
        ]);

        // Update media count on the page
        $this->connectionPool->getConnectionForTable('pages')->update('pages', ['media' => 1], ['uid' => $pageUid]);
    }

    /**
     * Generates a unique URL slug for a new blog post page.
     */
    private function generateSlug(string $title): string
    {
        $slug     = mb_strtolower($title);
        $slug     = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug     = trim($slug, '-');
        $slug     = $slug === '' ? 'blog-post' : $slug;

        $testSlug = '/' . $slug;
        $counter  = 0;

        while ($this->slugExists($testSlug)) {
            $counter++;
            $testSlug = '/' . $slug . '-' . $counter;
        }

        return $testSlug;
    }

    /**
     * Builds a short, filesystem-safe slug from the post title for use as image filename.
     * Replaces German umlauts, lowercases, strips special characters, caps at 50 chars.
     */
    private function buildImageSlug(string $title): string
    {
        $slug = str_replace(
            ['ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß'],
            ['ae', 'oe', 'ue', 'ae', 'oe', 'ue', 'ss'],
            $title
        );
        $slug = mb_strtolower($slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');
        $slug = $slug === '' ? 'bild' : $slug;
        return mb_substr($slug, 0, 50);
    }

    private function slugExists(string $slug): bool
    {
        try {
            $count = $this->connectionPool->getConnectionForTable('pages')->fetchOne(
                'SELECT COUNT(*) FROM pages WHERE slug = ? AND deleted = 0 AND doktype = 137',
                [$slug]
            );
            return (int) $count > 0;
        } catch (\Exception) {
            return false;
        }
    }
}
