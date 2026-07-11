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
            /* ==========================================================
             * Google
             * ======================================================== */
            'Googlebot',                    // Google Search
            'Googlebot-Image',              // Google Images
            'Googlebot-News',               // Google News
            'Googlebot-Video',              // Google Video
            'Google-InspectionTool',        // Search Console URL Inspection
            'GoogleOther',                  // Новый универсальный crawler Google
            'GoogleOther-Image',            // Google Images (универсальный)
            'GoogleOther-Video',            // Google Video (универсальный)
            'Storebot-Google',              // Google Play Store
            'Mediapartners-Google',         // Google AdSense
            'AdsBot-Google',                // Google Ads
            'APIs-Google',                  // Google APIs
            'FeedFetcher-Google',           // Google RSS Feed Fetcher
            'GoogleProducer',               // Google Publisher Center

            /* ==========================================================
             * Microsoft
             * ======================================================== */
            'Bingbot',                      // Bing Search
            'AdIdxBot',                     // Bing Ads
            'MicrosoftPreview',             // Microsoft Link Preview
            'SkypeUriPreview',              // Skype Link Preview

            /* ==========================================================
             * Yahoo
             * ======================================================== */
            'Slurp',                        // Yahoo Search

            /* ==========================================================
             * Apple
             * ======================================================== */
            'Applebot',                     // Apple Search (Siri, Spotlight и др.)

            /* ==========================================================
             * Yandex
             * ======================================================== */
            'YandexBot',                    // Yandex Search
            'YandexMobileBot',              // Yandex Mobile Search
            'YandexImages',                 // Yandex Images
            'YandexImageResizer',           // Yandex Image Resizer
            'YandexVideo',                  // Yandex Video
            'YandexMedia',                  // Yandex Media
            'YandexNews',                   // Yandex News
            'YandexBlogs',                  // Yandex Blogs
            'YandexFavicons',               // Yandex Favicons
            'YandexPagechecker',            // Yandex Page Checker
            'YandexWebmaster',              // Yandex Webmaster Tools

            /* ==========================================================
             * Chinese Search
             * ======================================================== */
            'Baiduspider',                  // Baidu Search (Китай)
            'Sogou',                        // Sogou Search (Китай)
            '360Spider',                    // 360 Search (Китай)
            'Bytespider',                   // ByteDance (TikTok) Search/AI

            /* ==========================================================
             * Other Search Engines
             * ======================================================== */
            'DuckDuckBot',                  // DuckDuckGo Search
            'MojeekBot',                    // Mojeek Search
            'PetalBot',                     // Huawei Petal Search
            'SeznamBot',                    // Seznam (Чехия)
            'Qwantbot',                     // Qwant Search (Франция)
            'Neevabot',                     // Neeva Search (AI)

            /* ==========================================================
             * SEO Crawlers
             * ======================================================== */
            'AhrefsBot',                    // Ahrefs SEO (backlink analysis)
            'SemrushBot',                   // Semrush SEO
            'DotBot',                       // Moz / Dotdash
            'MJ12bot',                      // Majestic SEO
            'BLEXBot',                      // BLEXBot (SEO)
            'SiteAuditBot',                 // SiteAudit SEO
            'Screaming Frog SEO Spider',    // Screaming Frog SEO Spider

            /* ==========================================================
             * AI Crawlers
             * ======================================================== */
            'GPTBot',                       // OpenAI (ChatGPT indexing)
            'ChatGPT-User',                 // ChatGPT User Agent
            'Claude-Web',                   // Anthropic Claude
            'anthropic-ai',                 // Anthropic AI Crawler
            'PerplexityBot',                // Perplexity AI
            'CCBot',                        // Common Crawl (данные для AI)
            'cohere-ai',                    // Cohere AI
            'OAI-SearchBot',                // OpenAI Search
            'Amazonbot',                    // Amazon AI / Alexa
            'Applebot-Extended',            // Apple AI (Extended)
            'ClaudeBot',                    // Anthropic Claude Bot
            'Meta-ExternalAgent',           // Meta AI (Facebook/Instagram)
            'Meta-ExternalFetcher',         // Meta External Fetcher
            'Diffbot',                      // Diffbot (структурированные данные)
            'YouBot',                       // You.com AI Search
            'VelenPublicWebCrawler',        // Velen AI Crawler
            'The Knowledge AI',             // The Knowledge AI

            /* ==========================================================
             * Social Networks / Messengers
             * ======================================================== */
            'facebookexternalhit',          // Facebook External Hit (sharing)
            'Facebot',                      // Facebook Bot
            'Twitterbot',                   // Twitter / X Bot
            'LinkedInBot',                  // LinkedIn Bot
            'Slackbot',                     // Slack Link Unfurling
            'Slack-ImgProxy',               // Slack Image Proxy
            'Discordbot',                   // Discord Link Preview
            'TelegramBot',                  // Telegram Link Preview
            'WhatsApp',                     // WhatsApp Link Preview
            'Pinterestbot',                 // Pinterest Bot

            /* ==========================================================
             * RSS Readers
             * ======================================================== */
            'Feedly',                       // Feedly RSS Reader
            'Feedburner',                   // Google Feedburner

            /* ==========================================================
             * Web Archive
             * ======================================================== */
            'ia_archiver',                  // Internet Archive (Wayback Machine)

            /* ==========================================================
             * Performance / Audit
             * ======================================================== */
            'Chrome-Lighthouse',            // Google Lighthouse (PageSpeed)
            'Lighthouse',                   // Google Lighthouse
        ];
    }
}
