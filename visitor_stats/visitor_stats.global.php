<?php
/**
 * [BEGIN_COT_EXT]
 * Hooks=global
 * [END_COT_EXT]
 */
/**
 * Global hook – record visits, block unwanted bots, auto‑cleanup every 72 hours
 * File: plugins/visitor_stats/visitor_stats.global.php
 * 
 * Date: June 23Th, 2026
 * 
 * @package visitor_stats
 * @version 1.0.28
 * @author webitproff
 * @copyright Copyright (c) webitproff 2026 | https://github.com/webitproff/visitor-stats-crawler-cotonti
 * @license BSD
 */

defined('COT_CODE') or die('Wrong URL');

if (!cot_plugin_active('visitor_stats')) {
    return;
}

require_once cot_incfile('visitor_stats', 'plug');

// ========== НАСТРОЙКА АВТООЧИСТКИ ==========
$autoCleanup = true;          // true — автоматически чистить таблицы раз в 72 часа, false — отключить
// ==========================================

if ($autoCleanup) {
    $cleanupDir = __DIR__ . '/last_cleanup';
    $cleanupFlag = $cleanupDir . '/timestamp';
    $now = time();
    $doCleanup = false;

    if (!is_dir($cleanupDir)) {
        @mkdir($cleanupDir, 0755, true);
    }

    if (@file_exists($cleanupFlag)) {
        $lastCleanup = (int) @file_get_contents($cleanupFlag);
        if ($lastCleanup > 0 && ($now - $lastCleanup) >= 72 * 3600) {
            $doCleanup = true;
        }
    } else {
        $doCleanup = true;
    }

    if ($doCleanup) {
        Cot::$db->delete(Cot::$db->visitor_stats, '1=1');
        Cot::$db->delete(Cot::$db->visitor_stats_daily, '1=1');
        Cot::$db->delete(Cot::$db->visitor_stats_crawlers, '1=1');
        @file_put_contents($cleanupFlag, $now);
    }
}

// ========== НАСТРОЙКА ОТЛАДКИ ==========
$debug = true; // true — писать лог (блокировки + разрешённые боты), false — молчать
// ==========================================

$crawlerDetect = CrawlerDetectService::getInstance();
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$is_crawler = $crawlerDetect->isCrawler($ua);
$crawlerName = $is_crawler ? $crawlerDetect->getCrawlerName($ua) : null;

// ========== ЭВРИСТИКА ПОДОЗРИТЕЛЬНЫХ UA ==========
// Блокируем явные эмуляции старых устройств, которые библиотека может не распознать как краулера
if (!$is_crawler) {
    $suspicious = false;
    if (preg_match('/Android [4-6]\./', $ua)) {
        $suspicious = true;
    } else {
        $suspiciousModels = ['Nexus 5', 'Nexus 5X', 'Nexus 6P', 'Pixel', 'SM-G900'];
        foreach ($suspiciousModels as $model) {
            if (stripos($ua, $model) !== false) {
                $suspicious = true;
                break;
            }
        }
    }
    if ($suspicious) {
        $is_crawler = true;
        $crawlerName = 'Suspicious UA';
    }
}
// =================================================

if ($debug) {
    $logEntry = date('Y-m-d H:i:s')
        . " UA: $ua"
        . " | name: " . ($crawlerName ?: 'unknown')
        . " | " . ($is_crawler ? (isAllowedBot($crawlerName) ? 'ALLOWED' : 'BLOCKED') : 'HUMAN')
        . "\n";
    file_put_contents(__DIR__ . '/debug_bot.log', $logEntry, FILE_APPEND);
}

if ($is_crawler && !isAllowedBot($crawlerName)) {
    // Записать заблокированный визит в БД
    $visitorService = VisitorStatsService::getInstance();
    $visitorService->recordVisit($ua, $crawlerName, true);

    // Отдать 403 с HTML-содержимым (для PageSpeed Insights)
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<html><body><h1>403 Forbidden</h1><p>Access denied for this bot.</p></body></html>';
    exit;
}

// Обычный визит (бот разрешён или человек)
$visitorService = VisitorStatsService::getInstance();
$visitorService->recordVisit($ua, $crawlerName, false);
