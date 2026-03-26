<?php

namespace Okay\Modules\Sviat\Ringostat\Helpers;

/**
 * Формат номера для відображення та перевірка "чи телефон" (без залежностей від Design/Module).
 */
class RingostatPhoneFormatHelper
{
    /**
     * Тільки цифри з номера (для порівняння, пошуку в БД).
     */
    public static function getDigits(string $value): string
    {
        return preg_replace('/\D/', '', $value);
    }

    /**
     * Відображення: +380501234567 → +380 50 123 45 67 (код України +380). Інакше повертає рядок як є.
     */
    public static function formatDisplay(string $value): string
    {
        $value = trim($value);
        $digits = self::getDigits($value);
        if (strlen($digits) === 12 && substr($digits, 0, 2) === '38') {
            return '+380 ' . substr($digits, 3, 2) . ' ' . substr($digits, 5, 3) . ' ' . substr($digits, 8, 2) . ' ' . substr($digits, 10, 2);
        }
        if (strlen($digits) === 10 && isset($digits[0]) && $digits[0] === '0') {
            return '+380 ' . substr($digits, 1, 2) . ' ' . substr($digits, 3, 3) . ' ' . substr($digits, 6, 2) . ' ' . substr($digits, 8, 2);
        }
        return $value;
    }

    /**
     * Чи схоже на повноцінний UA-номер (для tel:).
     */
    public static function isPhone(string $value): bool
    {
        $value = trim($value);
        if (preg_match('/[a-zA-Z]/', $value)) {
            return false;
        }
        $digits = self::getDigits($value);
        if (strlen($digits) === 12 && substr($digits, 0, 2) === '38') {
            return true;
        }
        if (strlen($digits) === 10 && isset($digits[0]) && $digits[0] === '0') {
            return true;
        }
        return false;
    }
}
