<?php

namespace Okay\Modules\Sviat\Ringostat\Helpers;

/**
 * Cron: синхронізація дзвінків з Ringostat API в локальну таблицю.
 * Рекомендовано запускати раз на годину або кілька разів на день.
 */
class RingostatCronHelper
{
    /** @var RingostatHelper */
    private $ringostatHelper;

    public function __construct(RingostatHelper $ringostatHelper)
    {
        $this->ringostatHelper = $ringostatHelper;
    }

    /**
     * Завантажити дзвінки за останні 24 години з Ringostat і зберегти в БД.
     * Використовує інкрементальну синхронізацію (forceFull = false): лише нові записи та оновлення IN PROGRESS/CALLING.
     */
    public function syncCalls(): void
    {
        $to = date('Y-m-d H:i:s');
        $from = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $this->ringostatHelper->syncCallsFromApi($from, $to);
    }
}
