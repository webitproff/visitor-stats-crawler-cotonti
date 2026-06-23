<?php
/**
 * [BEGIN_COT_EXT]
 * Hooks=global
 * [END_COT_EXT]
 */
/**
 * Global hook to record visitor statistics
 * Records on every page load
 * File: plugins/visitor_stats/visitor_stats.global.php
 * @package VisitorStats
 * @copyright (c) Cotonti Team
 * @license https://github.com/Cotonti/Cotonti/blob/master/License.txt
 */

defined('COT_CODE') or die('Wrong URL');

if (!cot_plugin_active('visitor_stats')) {
    return;
}

require_once cot_incfile('visitor_stats', 'plug');

// Record visit
$visitorService = VisitorStatsService::getInstance();
$visitorService->recordVisit();