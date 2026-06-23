<?php
/**
 * Visitor Statistics Functions
 * /plugins/visitor_stats/inc/visitor_stats.functions.php
 * @package VisitorStats
 * @copyright (c) Cotonti Team
 * @license https://github.com/Cotonti/Cotonti/blob/master/License.txt
 */

defined('COT_CODE') or die('Wrong URL');

// ========== Обязательные подключения и регистрация таблиц ==========
require_once cot_langfile('visitor_stats', 'plug');
require_once __DIR__ . '/CrawlerDetectService.php';
require_once __DIR__ . '/VisitorStatsRepository.php';
require_once __DIR__ . '/VisitorStatsService.php';

Cot::$db->registerTable('visitor_stats');
Cot::$db->registerTable('visitor_stats_daily');
Cot::$db->registerTable('visitor_stats_crawlers');

// ========== Информационные функции (доступны в шаблонах) ==========

/**
 * Реальный IP с учётом Cloudflare / прокси
 */
function getRealIp()
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    }
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = '0.0.0.0';
    }
    return $ip;
}

/**
 * User-Agent
 */
function getUserAgent()
{
    return $_SERVER['HTTP_USER_AGENT'] ?? '';
}

/**
 * Страна (Cloudflare, иначе пусто)
 */
function getCountry()
{
    if (!empty($_SERVER['HTTP_CF_IPCOUNTRY']) && $_SERVER['HTTP_CF_IPCOUNTRY'] != 'XX') {
        return $_SERVER['HTTP_CF_IPCOUNTRY'];
    }
    return '';
}

/**
 * Браузер и версия
 */
function getBrowser($ua = null)
{
    $ua = $ua ?: getUserAgent();
    if (preg_match('/Firefox\/([0-9.]+)/', $ua, $m)) {
        return 'Firefox ' . $m[1];
    }
    if (preg_match('/Chrome\/([0-9.]+)/', $ua, $m) && !preg_match('/Edg|OPR|YaBrowser|Vivaldi|Brave/', $ua)) {
        return 'Chrome ' . $m[1];
    }
    if (preg_match('/Safari\/([0-9.]+)/', $ua, $m) && !preg_match('/Chrome|Android/', $ua)) {
        return 'Safari ' . $m[1];
    }
    if (preg_match('/Edg\/([0-9.]+)/', $ua, $m)) {
        return 'Edge ' . $m[1];
    }
    if (preg_match('/OPR\/([0-9.]+)/', $ua, $m)) {
        return 'Opera ' . $m[1];
    }
    if (preg_match('/MSIE ([0-9.]+)/', $ua, $m)) {
        return 'IE ' . $m[1];
    }
    return 'Unknown';
}

/**
 * Операционная система
 */
function getOS($ua = null)
{
    $ua = $ua ?: getUserAgent();
    if (preg_match('/Windows NT 10\.0/', $ua)) {
        return 'Windows 10/11';
    }
    if (preg_match('/Windows NT 6\.3/', $ua)) {
        return 'Windows 8.1';
    }
    if (preg_match('/Windows NT 6\.2/', $ua)) {
        return 'Windows 8';
    }
    if (preg_match('/Windows NT 6\.1/', $ua)) {
        return 'Windows 7';
    }
    if (preg_match('/Mac OS X ([0-9_]+)/', $ua, $m)) {
        return 'macOS ' . str_replace('_', '.', $m[1]);
    }
    if (preg_match('/Linux/', $ua) && !preg_match('/Android/', $ua)) {
        return 'Linux';
    }
    if (preg_match('/Android ([0-9.]+)/', $ua, $m)) {
        return 'Android ' . $m[1];
    }
    if (preg_match('/iPhone|iPad|iPod/', $ua)) {
        return 'iOS';
    }
    return 'Unknown';
}

/**
 * Тип устройства
 */
function getDeviceType($ua = null)
{
    $ua = $ua ?: getUserAgent();
    if (preg_match('/Mobile|Android.*Mobile|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i', $ua)) {
        if (preg_match('/iPad|Tablet|Android(?!.*Mobile)/i', $ua)) {
            return 'Tablet';
        }
        return 'Mobile';
    }
    return 'Desktop';
}

/**
 * Модель устройства
 */
function getDeviceModel($ua = null)
{
    $ua = $ua ?: getUserAgent();
    if (preg_match('/Android [\d.]+; (.*?) Build/', $ua, $m)) {
        return trim(preg_replace('/;.*$/', '', $m[1]));
    }
    if (preg_match('/(iPhone|iPad)[^\d]*([\d,]+)/', $ua, $m)) {
        return $m[1] . ' ' . str_replace(',', '.', $m[2]);
    }
    if (strpos($ua, 'Windows NT') !== false) {
        return 'PC';
    }
    return '';
}

/**
 * ISP и VPN через ip-api.com (кэш в сессии)
 */
function getIspInfo()
{
    $ip = getRealIp();
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $key = 'ipinfo_' . md5($ip);
    if (!empty($_SESSION[$key]) && $_SESSION[$key]['exp'] > time()) {
        return $_SESSION[$key]['data'];
    }

    $ch = curl_init("http://ip-api.com/json/{$ip}?fields=isp,proxy,hosting");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 3]);
    $resp = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($resp, true);

    $result = null;
    if (!empty($data['isp'])) {
        $result = [
            'isp'    => $data['isp'],
            'is_vpn' => ($data['proxy'] || $data['hosting']) ? 1 : 0
        ];
    }

    $_SESSION[$key] = ['data' => $result, 'exp' => time() + 3600];
    return $result;
}

/**
 * Уникальный посетитель (сессия)
 */
function isUniqueVisitor()
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['visitor_stats_visited'])) {
        $_SESSION['visitor_stats_visited'] = 1;
        return 1; // новый
    }
    return 0; // вернувшийся
}