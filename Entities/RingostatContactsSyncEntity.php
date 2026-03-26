<?php

namespace Okay\Modules\Sviat\Ringostat\Entities;

use Okay\Core\Entity\Entity;

/**
 * Контроль синхронізації контактів OkayCMS ↔ Ringostat.
 */
class RingostatContactsSyncEntity extends Entity
{
    protected static $fields = [
        'id',
        'user_id',
        'phone',
        'name',
        'last_order_id',
        'last_order_sum',
        'synced_at',
        'sync_status',
        'ringostat_contact_id',
    ];

    protected static $table = 'sviat__ringostat_contacts_sync';
    protected static $tableAlias = 'rscs';

    protected static $defaultOrderFields = [
        'synced_at DESC',
        'id DESC',
    ];
}
