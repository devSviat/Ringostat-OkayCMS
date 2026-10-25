<?php

namespace Okay\Modules\Sviat\Ringostat\Extenders;

use Okay\Core\Modules\Extender\ExtensionInterface;
use Okay\Modules\Sviat\Ringostat\Helpers\RingostatHelper;

class FrontendExtender implements ExtensionInterface
{
    /** @var RingostatHelper */
    private $ringostatHelper;

    /** @var \Okay\Core\Settings */
    private $settings;

    public function __construct(
        RingostatHelper $ringostatHelper,
        \Okay\Core\Settings $settings
    ) {
        $this->ringostatHelper = $ringostatHelper;
        $this->settings = $settings;
    }

    /** Після створення замовлення — синхронізувати контакт у Ringostat. */
    public function syncContactAfterOrder($null, $order): void
    {
        if (empty($order) || empty($order->id)) {
            return;
        }
        $this->ringostatHelper->syncContactFromOrder($order);
    }

    /** Після реєстрації користувача — синхронізувати контакт у Ringostat. */
    public function syncContactAfterUserRegister($userId, $user): void
    {
        if (empty($userId) || empty($user)) {
            return;
        }
        $userObj = is_object($user) ? $user : (object)$user;
        if (empty($userObj->id)) {
            $userObj->id = $userId;
        }
        $this->ringostatHelper->syncContactFromUser($userObj);
    }
}
