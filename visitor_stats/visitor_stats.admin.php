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
 * Date: June 27Th, 2026
 * 
 * @package visitor_stats
 * @version 1.0.29
 * @author webitproff
 * @copyright Copyright (c) webitproff 2026 | https://github.com/webitproff/visitor-stats-crawler-cotonti
 * @license BSD
 */

(defined('COT_CODE') && defined('COT_ADMIN')) or die('Wrong URL.');

list(Cot::$usr['auth_read'], Cot::$usr['auth_write'], Cot::$usr['isadmin']) = cot_auth('plug', 'visitor_stats');
cot_block(Cot::$usr['auth_read']);

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
$days          = cot_import('days', 'G', 'INT', 0);
if ($days < 1) {
    $days = 30;
}
$only_bots     = cot_import('only_bots', 'G', 'INT', 0);
$show_blocked  = cot_import('show_blocked', 'G', 'INT', 0);
$filter_referer = cot_import('referer', 'G', 'TXT', '');
$filter_referer = (string) $filter_referer;
$limit = 50;
list($pg, $d, $durl) = cot_import_pagenav('d', $limit);

$t_vis = Cot::$db->quoteTableName(Cot::$db->visitor_stats);

// --- Основная статистика (без фильтра времени) ---
$total_human = Cot::$db->query("SELECT COUNT(*) FROM $t_vis WHERE vs_crawler_name IS NULL")->fetchColumn();
$total_bot   = Cot::$db->query("SELECT COUNT(*) FROM $t_vis WHERE vs_crawler_name IS NOT NULL")->fetchColumn();
$total_visits = $total_human + $total_bot;
$unique_visitors = Cot::$db->query("SELECT COUNT(DISTINCT vs_ip) FROM $t_vis")->fetchColumn();
$total_blocked   = Cot::$db->query("SELECT COUNT(*) FROM $t_vis WHERE vs_blocked = 1")->fetchColumn();

// --- Детальный лог ---
$condition = '1=1';
$params = [];
if ($only_bots) {
    $condition .= ' AND vs_crawler_name IS NOT NULL';
}
if ($show_blocked) {
    $condition .= ' AND vs_blocked = 1';
}
if (!empty($filter_referer)) {
    $condition .= ' AND vs_referer LIKE ?';
    $params[] = '%' . $filter_referer . '%';
}

$total_items = Cot::$db->query("SELECT COUNT(*) FROM $t_vis WHERE $condition", $params)->fetchColumn();

$log_entries = Cot::$db->query(
    "SELECT * FROM $t_vis WHERE $condition ORDER BY vs_date DESC LIMIT $d, $limit",
    $params
)->fetchAll(PDO::FETCH_ASSOC);

// Передача значений в шаблон
$tt->assign([
    'VAL_TOTAL'         => $total_visits,
    'VAL_HUMAN'         => $total_human,
    'VAL_BOT'           => $total_bot,
    'VAL_UNIQUE'        => $unique_visitors,
    'VAL_TOTAL_BLOCKED' => $total_blocked,
    'VAL_DAYS'          => $days,
    'VAL_ONLY_BOTS'     => $only_bots,
    'VAL_SHOW_BLOCKED'  => $show_blocked,
    'VAL_FILTER_REFERER'=> htmlspecialchars($filter_referer),
    'VAL_PAGE'          => $pg,
    'VAL_TOTAL_PAGES'   => ceil($total_items / $limit),
    'VAL_TOTAL_ITEMS'   => $total_items,
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
        'LOG_BLOCKED'      => $row['vs_blocked'] ? Cot::$L['visitor_stats_blocked_yes'] : Cot::$L['visitor_stats_blocked_no'],
        'LOG_BLOCKED_CLASS' => $row['vs_blocked'] ? 'table-danger' : 'table-success',
    ]);
    $tt->parse('MAIN.LOG_ROW');
    $ii++;
}

// Пагинация
$urlParams = [
    'm' => 'other',
    'p' => 'visitor_stats',
    'days' => $days,
    'only_bots' => $only_bots,
    'show_blocked' => $show_blocked,
    'referer' => $filter_referer,
];
$pagination = cot_pagenav('admin', $urlParams, $d, $total_items, $limit);
$tt->assign(cot_generatePaginationTags($pagination));


$tt->parse('MAIN');
$pluginBody = $tt->text('MAIN');
