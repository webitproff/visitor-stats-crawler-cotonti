<?php
/**
 * Visitor Statistics Functions
 * plugins/visitor_stats/inc/visitor_stats.functions.php
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

// ========== Обязательные подключения и регистрация таблиц ==========
require_once cot_langfile('visitor_stats', 'plug'); // файлы локализации
require_once __DIR__ . '/CrawlerDetectService.php';
require_once __DIR__ . '/VisitorStatsRepository.php';
require_once __DIR__ . '/VisitorStatsService.php';
require_once __DIR__ . '/../lib/Fixtures/WhitelistBots.php';

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
 * Получить информацию об ISP и VPN-статусе через ip-api.com (кэш в сессии)
 * совместимо с PHP 8.5
 * @return array|null ['isp' => string, 'is_vpn' => int] или null при ошибке
 */
function getIspInfo(): ?array
{
    $ip = getRealIp();
    
    // Инициализация сессии, если нужно
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    $key = 'ipinfo_' . md5($ip);
    
    // Проверяем кэш
    if (!empty($_SESSION[$key]) && is_array($_SESSION[$key]) && ($_SESSION[$key]['exp'] ?? 0) > time()) {
        return $_SESSION[$key]['data'];
    }
    
    // Современный способ инициализации cURL (без deprecated функций)
    $ch = curl_init();
    if ($ch === false) {
        error_log('Visitor Stats: Не удалось инициализировать cURL');
        return null;
    }
    
    // Формируем URL с нужными полями
    $url = sprintf('http://ip-api.com/json/%s?fields=isp,proxy,hosting', $ip);
    
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 3,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_FAILONERROR    => false, // Не хотим, чтобы ошибки HTTP приводили к false без тела
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1, // Современная версия протокола
    ]);
    
    $response = curl_exec($ch);
    
    // Проверка ошибок cURL
    if ($response === false) {
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        error_log("Visitor Stats: Ошибка cURL ({$errno}): {$error} для IP {$ip}");
        // Объект cURL закроется автоматически при выходе, не вызываем curl_close()
        return null;
    }
    
    // Проверяем HTTP-статус
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode !== 200) {
        error_log("Visitor Stats: ip-api вернул HTTP-статус {$httpCode} для IP {$ip}");
        return null;
    }
    
    // Декодируем JSON строго с проверкой ошибок
    $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    
    // Формируем результат только если есть ISP
    if (empty($data['isp'])) {
        $result = null;
    } else {
        $result = [
            'isp'    => (string)$data['isp'],
            'is_vpn' => (!empty($data['proxy']) || !empty($data['hosting'])) ? 1 : 0,
        ];
    }
    
    // Сохраняем в сессию с временем жизни
    $_SESSION[$key] = [
        'data' => $result,
        'exp'  => time() + 3600,
    ];
    
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
/**
 * Проверяет, разрешён ли доступ данному краулеру (на основе белого списка)
 *
 * @param string|null $crawlerName Имя краулера (null, если не бот)
 * @return bool true — разрешён, false — заблокировать
 */
function isAllowedBot($crawlerName)
{
    // Человек — всегда разрешён
    if ($crawlerName === null || $crawlerName === '') {
        return true;
    }

    // Подозрительные UA, маскирующиеся под старые устройства – блокируем
    if ($crawlerName === 'Suspicious UA') {
        return false;
    }

    $allowed = WhitelistBots::getAllowed();
    foreach ($allowed as $bot) {
        if (stripos($crawlerName, $bot) !== false) {
            return true;
        }
    }

    return false;
}
