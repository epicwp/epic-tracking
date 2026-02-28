<?php

namespace EpicTracking;

class UserAgentParser
{
    /**
     * Parse a user agent string into device type, browser, and OS.
     *
     * @return array{device_type: string, browser: string, os: string}
     */
    public static function parse(string $ua): array
    {
        return [
            'device_type' => self::detectDeviceType($ua),
            'browser'     => self::detectBrowser($ua),
            'os'          => self::detectOs($ua),
        ];
    }

    private static function detectDeviceType(string $ua): string
    {
        // Tablet patterns first (before mobile, since some tablets match mobile patterns)
        if (preg_match('/iPad|Android(?!.*Mobile)|Tablet|Kindle|Silk|PlayBook/i', $ua)) {
            return 'Tablet';
        }

        if (preg_match('/Mobile|iPhone|iPod|Android.*Mobile|webOS|BlackBerry|Opera Mini|IEMobile|Windows Phone/i', $ua)) {
            return 'Mobile';
        }

        return 'Desktop';
    }

    private static function detectBrowser(string $ua): string
    {
        // Order matters: check more specific browsers first
        if (preg_match('/Edg(e|A|iOS)?\//i', $ua)) {
            return 'Edge';
        }
        if (preg_match('/OPR\/|Opera\//i', $ua)) {
            return 'Opera';
        }
        if (preg_match('/SamsungBrowser\//i', $ua)) {
            return 'Samsung Internet';
        }
        if (preg_match('/Chrome\/|CriOS\//i', $ua) && !preg_match('/Edg|OPR|Opera|SamsungBrowser/i', $ua)) {
            return 'Chrome';
        }
        if (preg_match('/Safari\//i', $ua) && !preg_match('/Chrome|CriOS|Edg|OPR|Opera|SamsungBrowser/i', $ua)) {
            return 'Safari';
        }
        if (preg_match('/Firefox\/|FxiOS\//i', $ua)) {
            return 'Firefox';
        }
        if (preg_match('/MSIE|Trident\//i', $ua)) {
            return 'IE';
        }

        return 'Other';
    }

    private static function detectOs(string $ua): string
    {
        if (preg_match('/iPhone|iPad|iPod/i', $ua)) {
            return 'iOS';
        }
        if (preg_match('/Macintosh|Mac OS X/i', $ua)) {
            return 'macOS';
        }
        if (preg_match('/Android/i', $ua)) {
            return 'Android';
        }
        if (preg_match('/Windows/i', $ua)) {
            return 'Windows';
        }
        if (preg_match('/Linux/i', $ua) && !preg_match('/Android/i', $ua)) {
            return 'Linux';
        }
        if (preg_match('/CrOS/i', $ua)) {
            return 'Chrome OS';
        }

        return 'Other';
    }
}
