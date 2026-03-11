<?php

namespace EpicTracking;

class GeoIP
{
    public static function lookup(string $ip): array
    {
        $empty = ['country' => '', 'country_code' => ''];

        // Skip private/local IPs
        if (self::isPrivateIp($ip)) {
            return $empty;
        }

        // Check transient cache
        $cacheKey = 'epictr_geo_' . md5($ip);
        $cached = get_transient($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        // Query ip-api.com (free, no key required)
        $response = wp_remote_get("http://ip-api.com/json/{$ip}?fields=status,country,countryCode", [
            'timeout' => 3,
        ]);

        if (is_wp_error($response)) {
            return $empty;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body) || ($body['status'] ?? '') !== 'success') {
            return $empty;
        }

        $result = [
            'country'      => sanitize_text_field($body['country'] ?? ''),
            'country_code' => sanitize_text_field($body['countryCode'] ?? ''),
        ];

        // Cache for 24 hours
        set_transient($cacheKey, $result, DAY_IN_SECONDS);

        return $result;
    }

    private static function isPrivateIp(string $ip): bool
    {
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
