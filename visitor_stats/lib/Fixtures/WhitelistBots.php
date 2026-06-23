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
            'Googlebot',
            'Bingbot',
            'YandexBot',
            'DuckDuckBot',
            'Applebot',
            'FacebookExternalHit',
            'Twitterbot',
            'LinkedInBot',
            'WhatsApp',
            'TelegramBot',
            'Chrome-Lighthouse',
            'Lighthouse',           // https://pagespeed.web.dev/
            'Google-InspectionTool',
            'meta-externalagent',
            'AhrefsBot',
            'SemrushBot',
            'DotBot',
            'Baiduspider',
            'Slurp',
            'ia_archiver',
            'PetalBot',
            'MojeekBot',
            'SeznamBot',
            'Sogou',
            'YandexMobileBot',
            'Mediapartners-Google',
            'Feedburner',
            'Feedly',
			'GPTBot',              // OpenAI
			'ChatGPT-User',        // ChatGPT
			'Claude-Web',          // Anthropic
			'PerplexityBot',       // Perplexity AI
			'CCBot',               // Common Crawl (используется многими AI-стартапами)
			'The Knowledge AI',    // Knowledge AI
			'VelenPublicWebCrawler', // Velen
			'cohere-ai',           // Cohere
        ];
    }
}