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
 * @version 1.0.27
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
    // Папка для хранения временной метки (создаётся при первом обращении)
    $cleanupDir = __DIR__ . '/last_cleanup';
    $cleanupFlag = $cleanupDir . '/timestamp';
    $now = time();
    $doCleanup = false;

    // Создаём папку, если её ещё нет (права 0777 — чтение/запись для всех)
    // Создаём папку с правами 0755, если её ещё нет
    // Исправил: права доступа 0755 вместо 0777.
    // Папка будет создаваться с правами rwxr‑xr‑x (запись только для владельца‑процесса).
    if (!is_dir($cleanupDir)) {
        @mkdir($cleanupDir, 0755, true);
    }

    if (@file_exists($cleanupFlag)) {
        $lastCleanup = (int) @file_get_contents($cleanupFlag);
        if ($lastCleanup > 0 && ($now - $lastCleanup) >= 72 * 3600) {
            $doCleanup = true;
        }
    } else {
        // Файла нет – первый запуск или после очистки вручную
        $doCleanup = true;
    }

    if ($doCleanup) {
        Cot::$db->delete(Cot::$db->visitor_stats, '1=1');
        Cot::$db->delete(Cot::$db->visitor_stats_daily, '1=1');
        Cot::$db->delete(Cot::$db->visitor_stats_crawlers, '1=1');
        // Обновляем метку времени
        @file_put_contents($cleanupFlag, $now);
    }
}

// ========== НАСТРОЙКА ОТЛАДКИ ==========
$debug = false; // true — писать лог (блокировки + разрешённые боты), false — молчать
// ==========================================

$crawlerDetect = CrawlerDetectService::getInstance();
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$is_crawler = $crawlerDetect->isCrawler($ua);
$crawlerName = $is_crawler ? $crawlerDetect->getCrawlerName($ua) : null;

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
