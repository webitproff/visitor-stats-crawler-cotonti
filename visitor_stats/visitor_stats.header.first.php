<?php
/**
 * [BEGIN_COT_EXT]
 * Hooks=header.first
 * [END_COT_EXT]
 */

/**
 * Block unwanted bots (not in whitelist) early in the request
 * File: plugins/visitor_stats/visitor_stats.header.first.php
 * @package VisitorStats
 * @copyright (c) Cotonti Team
 * @license https://github.com/Cotonti/Cotonti/blob/master/License.txt
 */

defined('COT_CODE') or die('Wrong URL');

if (!cot_plugin_active('visitor_stats')) {
    return;
}

require_once cot_incfile('visitor_stats', 'plug');

// ========== НАСТРОЙКА ОТЛАДКИ ==========
$debug = false; // true — писать лог блокировок, false — не писать

$crawlerDetect = CrawlerDetectService::getInstance();
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

if ($crawlerDetect->isCrawler($ua)) {
    $crawlerName = $crawlerDetect->getCrawlerName($ua);
    $allowed = isAllowedBot($crawlerName);
    
    if ($debug) {
        $logEntry = date('Y-m-d H:i:s') 
            . " UA: $ua" 
            . " | name: " . ($crawlerName ?: 'unknown') 
            . " | " . ($allowed ? 'ALLOWED' : 'BLOCKED') 
            . "\n";
        file_put_contents(__DIR__ . '/debug_bot.log', $logEntry, FILE_APPEND);
    }
    
    if (!$allowed) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Access denied for this bot.';
        exit;
    }
}