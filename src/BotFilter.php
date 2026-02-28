<?php

namespace EpicTracking;

class BotFilter
{
    private static array $botPatterns = [
        'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider',
        'yandexbot', 'sogou', 'facebookexternalhit', 'twitterbot',
        'rogerbot', 'linkedinbot', 'embedly', 'showyoubot', 'outbrain',
        'pinterest', 'applebot', 'semrushbot', 'ahrefsbot', 'mj12bot',
        'dotbot', 'petalbot', 'bytespider',
        'bot', 'crawl', 'spider', 'scrape', 'fetch',
    ];

    public static function isBot(string $userAgent): bool
    {
        $ua = strtolower($userAgent);
        foreach (self::$botPatterns as $pattern) {
            if (strpos($ua, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }
}
