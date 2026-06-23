<?php
/**
 * Crawler Detection Service
 * Wrapper around Crawler-Detect library
 * File: plugins/visitor_stats/inc/CrawlerDetectService.php
 * @package VisitorStats
 * @copyright (c) Cotonti Team
 * @license https://github.com/Cotonti/Cotonti/blob/master/License.txt
 */

defined('COT_CODE') or die('Wrong URL');

require_once __DIR__ . '/../lib/Fixtures/AbstractProvider.php';
require_once __DIR__ . '/../lib/Fixtures/Headers.php';
require_once __DIR__ . '/../lib/Fixtures/Exclusions.php';
require_once __DIR__ . '/../lib/Fixtures/Crawlers.php';
require_once __DIR__ . '/../lib/CrawlerDetect.php';

class CrawlerDetectService
{
    private static $instance = null;

    private function __construct() {}

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Check if user agent belongs to a crawler/bot
     *
     * @param string|null $userAgent
     * @return bool
     */
    public function isCrawler($userAgent = null)
    {
        $detect = new CrawlerDetect(null, $userAgent);
        return $detect->isCrawler();
    }

    /**
     * Get crawler name if detected
     *
     * @param string|null $userAgent
     * @return string|null
     */
    public function getCrawlerName($userAgent = null)
    {
        $detect = new CrawlerDetect(null, $userAgent);
        if ($detect->isCrawler()) {
            $match = $detect->getMatches();
            return !empty($match) ? trim($match) : null;
        }
        return null;
    }

    /**
     * Get current user agent from the last detection (for backwards compatibility)
     *
     * @return string|null
     */
    public function getUserAgent()
    {
        $detect = new CrawlerDetect();
        return $detect->getUserAgent();
    }
}