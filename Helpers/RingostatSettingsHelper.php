<?php

namespace Okay\Modules\Sviat\Ringostat\Helpers;

use Okay\Core\Settings;

/**
 * Читання/запис налаштувань модуля через глобальні Settings.
 */
class RingostatSettingsHelper
{
    /** @var Settings */
    private $settings;

    private const KEY_MAP = [
        'api_key' => 'sviat__ringostat__auth_key',
        'project_id' => 'sviat__ringostat__project_id',
        'sync_contacts_enabled' => 'sviat__ringostat__sync_contacts_enabled',
        'show_order_info_in_phone' => 'sviat__ringostat__show_order_info_in_phone',
        'contact_name_format' => 'sviat__ringostat__contact_name_format',
    ];

    public const CONTACT_NAME_FORMAT_NAME_LASTNAME = 'name_lastname';
    public const CONTACT_NAME_FORMAT_NAME_ORDER_ID = 'name_order_id';
    public const CONTACT_NAME_FORMAT_ORDER_ID = 'order_id';

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    /** Значення налаштування за внутрішнім ключем (api_key, project_id, …). */
    public function get(string $key)
    {
        $settingsKey = self::KEY_MAP[$key] ?? null;
        if ($settingsKey === null) {
            return null;
        }
        $v = $this->settings->get($settingsKey);
        if ($key === 'sync_contacts_enabled' && ($v === null || $v === '')) {
            return $this->settings->get('sviat__ringostat__sync_on_order');
        }
        return $v;
    }

    public function getSyncContactsEnabled(): bool
    {
        $v = $this->get('sync_contacts_enabled');
        $syncOnOrder = (int) $this->settings->get('sviat__ringostat__sync_on_order') === 1;
        if ($v === null || $v === '') {
            return $syncOnOrder;
        }
        return (int) $v === 1 || $syncOnOrder;
    }

    public function getShowOrderInfoInPhone(): bool
    {
        $v = $this->get('show_order_info_in_phone');
        if ($v === null || $v === '') {
            return true;
        }
        return (int) $v === 1;
    }

    /**
     * Формат імені контакту при передачі в minicrm/contacts/sync: name_lastname | name_order_id | order_id
     */
    public function getContactNameFormat(): string
    {
        $v = $this->get('contact_name_format');
        if (in_array($v, [self::CONTACT_NAME_FORMAT_NAME_LASTNAME, self::CONTACT_NAME_FORMAT_NAME_ORDER_ID, self::CONTACT_NAME_FORMAT_ORDER_ID], true)) {
            return $v;
        }
        return self::CONTACT_NAME_FORMAT_NAME_LASTNAME;
    }

    public function setAll(array $data): void
    {
        foreach (self::KEY_MAP as $field => $settingsKey) {
            if (array_key_exists($field, $data)) {
                $this->settings->set($settingsKey, $data[$field]);
            }
        }
    }
}
