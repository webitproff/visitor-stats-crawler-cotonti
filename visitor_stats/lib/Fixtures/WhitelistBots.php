<?php
/**
 * Whitelist of allowed bots
 * File: plugins/visitor_stats/lib/Fixtures/WhitelistBots.php
 */

class WhitelistBots
{
    public static function getAllowed(): array
    {
        return [
            'AhrefsBot',                // SEO tool (Ahrefs)
            'Applebot',                 // Apple search
            'Baiduspider',              // Baidu (Chinese search engine)
            'Bingbot',                  // Microsoft Bing
            'CCBot',                    // Common Crawl (used by many AI startups)
            'ChatGPT-User',             // ChatGPT
            'Chrome-Lighthouse',        // PageSpeed Insights / Lighthouse
            'Claude-Web',               // Anthropic AI
            'cohere-ai',                // Cohere AI
            'DotBot',                   // SEO / analytics (Moz)
            'DuckDuckBot',              // DuckDuckGo
            'FacebookExternalHit',      // Facebook crawler
            'Feedburner',               // Feed management
            'Feedly',                   // RSS reader
            'Google-InspectionTool',    // Google Search Console testing
            'Googlebot',                // Google search
            'Googlebot-Image',          // сканирует и индексирует визуальный контент от фавикона и выше
            'GPTBot',                   // OpenAI
            'ia_archiver',              // Internet Archive
            'Lighthouse',               // PageSpeed Insights / Lighthouse
            'LinkedInBot',              // LinkedIn
            'Mediapartners-Google',     // Google AdSense
            'meta-externalagent',       // Facebook sharing crawler
            'MojeekBot',                // Mojeek search engine
            'PerplexityBot',            // Perplexity AI
            'PetalBot',                 // Petal search (Huawei)
            'SemrushBot',               // SEO tool (Semrush)
            'SeznamBot',                // Seznam (Czech search engine)
            'Slurp',                    // Yahoo
            'Sogou',                    // Sogou (Chinese search engine)
            'TelegramBot',              // Telegram
            'The Knowledge AI',         // AI crawler
            'Twitterbot',               // Twitter/X
            'VelenPublicWebCrawler',    // Velen
            'WhatsApp',                 // WhatsApp
            'YandexBot',                // Yandex (Russian search engine)
            'YandexMobileBot',          // Yandex mobile
        ];
    }
}
