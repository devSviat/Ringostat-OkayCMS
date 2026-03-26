<?php

namespace Okay\Modules\Sviat\Ringostat\Backend\Helpers;

/**
 * Допоміжні методи для backend-контролерів Ringostat (дати, безпека редиректу).
 */
class RingostatBackendHelper
{
    /**
     * Повертає дату у форматі Y-m-d або $default, якщо вхід не валідний.
     */
    public static function sanitizeDateYmd(string $value, string $default): string
    {
        $value = trim($value);
        if ($value === '') {
            return $default;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $ts = strtotime($value);
            if ($ts !== false) {
                return date('Y-m-d', $ts);
            }
        }
        return $default;
    }

    /**
     * Дозволений редирект на https або http (запис з Ringostat).
     * http дозволено для локальної розробки та якщо CDN віддає http.
     */
    public static function validateRecordRedirectUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        $parsed = parse_url($url);
        if ($parsed === false || !isset($parsed['scheme']) || !isset($parsed['host'])) {
            return null;
        }
        $scheme = strtolower($parsed['scheme']);
        if ($scheme !== 'https' && $scheme !== 'http') {
            return null;
        }
        if (preg_match('/[\s<>"\'\\\\]/', $url)) {
            return null;
        }
        return $url;
    }
}
