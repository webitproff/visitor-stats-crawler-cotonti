<?php
/**
 * Visitor Statistics Service
 * Main business logic
 * File: plugins/visitor_stats/inc/VisitorStatsService.php
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

class VisitorStatsService
{
    private static $instance = null;
    private static $visitRecorded = false;

    private $crawlerDetect;
    private $repository;

    private function __construct()
    {
        $this->crawlerDetect = CrawlerDetectService::getInstance();
        $this->repository = VisitorStatsRepository::getInstance();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Record a visit
     *
     * @param string|null $ua User-Agent (null = брать из $_SERVER)
     * @param string|null $preDetectedCrawlerName Имя краулера, если уже определено
     * @param bool $blocked Является ли визит заблокированным
     */
    public function recordVisit($ua = null, $preDetectedCrawlerName = null, $blocked = false)
    {
        // Предотвращаем повторную запись в рамках одного запроса
        if (self::$visitRecorded) {
            return;
        }
        self::$visitRecorded = true;

        if ($ua === null) {
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        }

        // Если краулер уже определён (например, в header.first), используем готовое имя
        if ($preDetectedCrawlerName !== null) {
            $is_crawler = true;
            $crawler_name = $preDetectedCrawlerName;
        } else {
            // Обычная проверка
            $is_crawler = $this->crawlerDetect->isCrawler($ua);
            $crawler_name = $is_crawler ? $this->crawlerDetect->getCrawlerName($ua) : null;

            // Эвристика подозрительных UA
            if (!$is_crawler && $this->isSuspiciousUA($ua)) {
                $is_crawler = true;
                $crawler_name = 'Suspicious UA';
            }
        }

        // Собираем все данные полностью, независимо от блокировки
        $browser      = getBrowser($ua);
        $os           = getOS($ua);
        $device_type  = getDeviceType($ua);
        $device_model = getDeviceModel($ua);
        $country      = getCountry();
        $isp_data     = getIspInfo();
        $isp          = $isp_data['isp'] ?? null;
        $is_vpn       = $isp_data['is_vpn'] ?? 0;
        $unique       = $blocked ? 0 : isUniqueVisitor();

        $visit_data = [
            'vs_date'         => Cot::$sys['now'],
            'vs_ip'           => $this->getClientIp(),
            'vs_user_id'      => Cot::$usr['id'] > 0 ? (int)Cot::$usr['id'] : 0,
            'vs_referer'      => isset($_SERVER['HTTP_REFERER']) ? substr($_SERVER['HTTP_REFERER'], 0, 500) : '',
            'vs_user_agent'   => substr($ua, 0, 500),
            'vs_page'         => isset($_SERVER['REQUEST_URI']) ? substr($_SERVER['REQUEST_URI'], 0, 500) : '',
            'vs_crawler_name' => $crawler_name,
            'vs_browser'      => $browser,
            'vs_os'           => $os,
            'vs_device_type'  => $device_type,
            'vs_device_model' => $device_model,
            'vs_country'      => $country,
            'vs_isp'          => $isp,
            'vs_is_vpn'       => $is_vpn,
            'vs_is_bot'       => $is_crawler ? 1 : 0,
            'vs_unique'       => $unique,
            'vs_blocked'      => $blocked ? 1 : 0,
        ];

        $this->repository->insert($visit_data);
    }

    /**
     * Проверяет, является ли User-Agent подозрительным
     * (старая ОС, известная тестовая/устаревшая модель)
     *
     * @param string $ua
     * @return bool
     */
    private function isSuspiciousUA($ua)
    {
        // Старые версии Android (4.x, 5.x, 6.x)
        if (preg_match('/Android [4-6]\./', $ua)) {
            return true;
        }

        // Явно устаревшие тестовые модели, которыми обычно маскируются сканеры
        $suspiciousModels = [
            'Nexus 5',
            'Nexus 5X',
            'Nexus 6P',
            'Pixel',
            'SM-G900',   // Samsung Galaxy S5 и подобные
        ];
        foreach ($suspiciousModels as $model) {
            if (stripos($ua, $model) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get statistics for period
     *
     * @param int $days
     * @param bool $exclude_bots
     * @return array
     */
    public function getStatsForPeriod($days = 30, $exclude_bots = true)
    {
        return [
            'total_visits' => $this->repository->countVisits($days, false),
            'bot_visits' => $this->repository->countBotVisits($days),
            'human_visits' => $this->repository->countVisits($days, true),
            'unique_visitors' => $this->repository->countUniqueVisitors($days),
            'top_pages' => $this->repository->getTopPages($days, 10),
            'top_referers' => $this->repository->getTopReferers($days, 10),
            'top_crawlers' => $this->repository->getTopCrawlers($days, 10),
            'daily' => $this->repository->getDailyBreakdown($days),
        ];
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    private function getClientIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return trim(explode(',', $_SERVER['HTTP_CLIENT_IP'])[0]);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
