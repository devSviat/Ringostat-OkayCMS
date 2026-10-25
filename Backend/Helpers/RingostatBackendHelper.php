<?php

namespace Okay\Modules\Sviat\Ringostat\Backend\Helpers;

/**
 * Допоміжні методи для backend-контролерів Ringostat (дати, безпека редиректу).
 */
class RingostatBackendHelper
{
    /**
     * Кеш-мітка скрипта плеєра.
     *
     * Плеєр підключається прямим тегом, повз бандл, а дерево модулів nginx
     * віддає з `max-age` на десять років. Без мітки правка файлу не доїжджає
     * в браузер, який уже його завантажив.
     */
    public static function recordPlayerVersion(): int
    {
        return (int) @filemtime(dirname(__DIR__) . '/design/js/ringostat_record_player.js');
    }

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
     * Телефон із форми: E164 (+380…) або самі цифри. Інакше null.
     * Використовується як ключ рядка в черзі передзвону.
     */
    public static function sanitizePhone(string $phone): ?string
    {
        $phone = trim($phone);
        return preg_match('/^\+?\d{9,20}$/', $phone) === 1 ? $phone : null;
    }

    /** Чи це datetime у форматі БД (Y-m-d H:i:s). */
    public static function isDbDateTime(string $value): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', trim($value)) === 1;
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
