<?php

namespace Okay\Modules\Sviat\Ringostat\Entities;

use Okay\Core\Entity\Entity;

/**
 * Черга передзвону: номери з пропущеними вхідними (in + NO ANSWER/VOICEMAIL).
 * Запис видаляється, коли по номеру був успішний дзвінок (PROPER/ANSWERED).
 */
class RingostatCallbackQueueEntity extends Entity
{
    protected static $fields = [
        'id',
        'phone',
        'missed_count',
        'last_missed_at',
        'processed',
    ];

    protected static $table = 'sviat__ringostat_callback_queue';
    protected static $tableAlias = 'rscq';

    protected static $defaultOrderFields = [
        'last_missed_at DESC',
        'id DESC',
    ];

    protected function filter__processed($value): void
    {
        if ($value === '' || $value === null) {
            return;
        }
        $this->select->where('rscq.processed = :filter_processed')->bindValue('filter_processed', (int) $value);
    }
}
