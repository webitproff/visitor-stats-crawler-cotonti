<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=tools
[END_COT_EXT]
==================== */

/**
 * Administration panel - Visitor Statistics
 * File: plugins/visitor_stats/visitor_stats.admin.php
 * 
 * Date: June 23Th, 2026
 * 
 * @package visitor_stats
 * @version 1.0.27
 * @author webitproff
 * @copyright Copyright (c) webitproff 2026 | https://github.com/webitproff/visitor-stats-crawler-cotonti
 * @license BSD
 */

(defined('COT_CODE') && defined('COT_ADMIN')) or die('Wrong URL.');

list(Cot::$usr['auth_read'], Cot::$usr['auth_write'], Cot::$usr['isadmin']) = cot_auth('plug', 'visitor_stats');
cot_block(Cot::$usr['auth_read']);

require_once cot_langfile('visitor_stats', 'plug');
require_once cot_incfile('visitor_stats', 'plug');

$tt = new XTemplate(cot_tplfile('visitor_stats.admin', 'plug', true));

$adminTitle = Cot::$L['visitor_stats'];

// Очистка таблиц
$clear = cot_import('clear', 'G', 'INT', 0);
if ($clear && Cot::$usr['isadmin']) {
    Cot::$db->delete(Cot::$db->visitor_stats, '1=1');
    Cot::$db->delete(Cot::$db->visitor_stats_daily, '1=1');
    Cot::$db->delete(Cot::$db->visitor_stats_crawlers, '1=1');
    cot_redirect(cot_url('admin', 'm=other&p=visitor_stats', '', false));
}

// Параметры
$days      = cot_import('days', 'G', 'INT', 0);
if ($days < 1) {
    $days = 30;                 // значение по умолчанию, если не задано или 0
}
$only_bots = cot_import('only_bots', 'G', 'INT', 0);
$page      = cot_import('page', 'G', 'INT', 1);
if ($page < 1) $page = 1;
$limit  = 50;
$offset = ($page - 1) * $limit;

$t_vis = Cot::$db->quoteTableName(Cot::$db->visitor_stats);

// --- Основная статистика (без фильтра времени) ---
$total_human = Cot::$db->query("SELECT COUNT(*) FROM $t_vis WHERE vs_crawler_name IS NULL")->fetchColumn();
$total_bot   = Cot::$db->query("SELECT COUNT(*) FROM $t_vis WHERE vs_crawler_name IS NOT NULL")->fetchColumn();
$total_visits = $total_human + $total_bot;
$unique_visitors = Cot::$db->query("SELECT COUNT(DISTINCT vs_ip) FROM $t_vis")->fetchColumn();

// --- Детальный лог ---
$condition = '1=1';
$params = [];
if ($only_bots) {
    $condition .= ' AND vs_crawler_name IS NOT NULL';
}

$total_items = Cot::$db->query("SELECT COUNT(*) FROM $t_vis WHERE $condition", $params)->fetchColumn();
$total_pages = ceil($total_items / $limit);
if ($total_pages > 0 && $page > $total_pages) {
    $page   = $total_pages;
    $offset = ($page - 1) * $limit;
}

$log_entries = Cot::$db->query(
    "SELECT * FROM $t_vis WHERE $condition ORDER BY vs_date DESC LIMIT $limit OFFSET $offset",
    $params
)->fetchAll(PDO::FETCH_ASSOC);

// Передача значений в шаблон
$tt->assign([
    'VAL_TOTAL'       => $total_visits,
    'VAL_HUMAN'       => $total_human,
    'VAL_BOT'         => $total_bot,
    'VAL_UNIQUE'      => $unique_visitors,
    'VAL_DAYS'        => $days,
    'VAL_ONLY_BOTS'   => $only_bots,
    'VAL_PAGE'        => $page,
    'VAL_TOTAL_PAGES' => $total_pages,
    'VAL_TOTAL_ITEMS' => $total_items,
]);

$ii = 0;
foreach ($log_entries as $row) {
    $tt->assign([
        'LOG_DATE'         => date('Y-m-d H:i:s', $row['vs_date']),
        'LOG_IP'           => htmlspecialchars($row['vs_ip']),
        'LOG_COUNTRY'      => htmlspecialchars($row['vs_country'] ?? ''),
        'LOG_BROWSER'      => htmlspecialchars($row['vs_browser'] ?? ''),
        'LOG_OS'           => htmlspecialchars($row['vs_os'] ?? ''),
        'LOG_DEVICE_TYPE'  => htmlspecialchars($row['vs_device_type'] ?? ''),
        'LOG_DEVICE_MODEL' => htmlspecialchars($row['vs_device_model'] ?? ''),
        'LOG_ISP'          => htmlspecialchars($row['vs_isp'] ?? ''),
        'LOG_IS_VPN'       => $row['vs_is_vpn'] ? Cot::$L['Yes'] : Cot::$L['No'],
        'LOG_IS_BOT'       => $row['vs_is_bot'] ? Cot::$L['Yes'] : Cot::$L['No'],
        'LOG_UNIQUE'       => $row['vs_unique'] ? Cot::$L['Yes'] : Cot::$L['No'],
        'LOG_CRAWLER'      => htmlspecialchars($row['vs_crawler_name'] ?? ''),
        'LOG_PAGE'         => htmlspecialchars($row['vs_page']),
        'LOG_REFERER'      => htmlspecialchars($row['vs_referer'] ?? ''),
        'LOG_ODDEVEN'      => cot_build_oddeven($ii),
		'LOG_BLOCKED' => $row['vs_blocked'] ? Cot::$L['visitor_stats_blocked_yes'] : Cot::$L['visitor_stats_blocked_no'],
    ]);
    $tt->parse('MAIN.LOG_ROW');
    $ii++;
}

// Пагинация
if ($total_pages > 1) {
    $pagination = cot_pagenav(
        'admin',
        'm=other&p=visitor_stats&days=' . $days . '&only_bots=' . $only_bots,
        $offset,
        $total_items,
        $limit,
        'page'
    );
    $tt->assign(cot_generatePaginationTags($pagination));
    $tt->parse('MAIN.PAGINATION');
}

$tt->parse('MAIN');
$pluginBody = $tt->text('MAIN');
