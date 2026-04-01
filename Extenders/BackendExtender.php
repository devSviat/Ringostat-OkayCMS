<?php

namespace Okay\Modules\Sviat\Ringostat\Extenders;

use Okay\Core\EntityFactory;
use Okay\Core\ManagerMenu;
use Okay\Core\Modules\Extender\ExtensionInterface;
use Okay\Core\Phone;
use Okay\Core\Request;
use Okay\Modules\Sviat\Ringostat\Entities\RingostatCallbackQueueEntity;
use Okay\Modules\Sviat\Ringostat\Entities\RingostatCallsEntity;
use Okay\Modules\Sviat\Ringostat\Helpers\RingostatPhoneFormatHelper;

class BackendExtender implements ExtensionInterface
{
    /** @var ManagerMenu */
    private $managerMenu;

    /** @var EntityFactory */
    private $entityFactory;

    /** @var Request */
    private $request;

    public function __construct(
        ManagerMenu $managerMenu,
        EntityFactory $entityFactory,
        Request $request
    ) {
        $this->managerMenu = $managerMenu;
        $this->entityFactory = $entityFactory;
        $this->request = $request;
    }

    /** Бадж з кількістю необроблених у черзі передзвону. */
    public function setCallbackQueueCounter(): void
    {
        /** @var RingostatCallbackQueueEntity $queueEntity */
        $queueEntity = $this->entityFactory->get(RingostatCallbackQueueEntity::class);
        $this->managerMenu->addCounter('sviat__left_ringostat_callback_queue', $queueEntity->count(['processed' => 0]));
    }

    /**
     * Дзвінки Ringostat для картки замовлення (після дати замовлення; in→caller, out→callee як у журналі).
     *
     * @param object|null $order
     * @param mixed $orderId
     * @return object|null
     */
    public function findOrder($order, $orderId)
    {
        if (!$order || empty($order->id)) {
            return $order;
        }

        $order->sviat_ringostat_calls = [];

        $ctx = $this->ringostatOrderCallsQueryContext($order);
        if ($ctx === null) {
            return $order;
        }

        /** @var RingostatCallsEntity $callsEntity */
        $callsEntity = $this->entityFactory->get(RingostatCallsEntity::class);
        $calls = $callsEntity->findCallsForOrder($ctx['phone_like'], $ctx['order_from_sql']);

        $rootUrl = rtrim($this->request->getRootUrl(), '/');
        foreach ($calls as $call) {
            $this->enrichRingostatCallForOrderCard($call, $rootUrl, $ctx['caller_filter']);
        }

        $order->sviat_ringostat_calls = $calls;

        return $order;
    }

    /**
     * @return array{phone_like: string, caller_filter: string, order_from_sql: string}|null
     */
    private function ringostatOrderCallsQueryContext(object $order): ?array
    {
        $phoneE164 = Phone::toSave($order->phone ?? '');
        if ($phoneE164 === null || $phoneE164 === '') {
            $phoneE164 = Phone::toSave(trim((string) ($order->phone ?? ''))) ?: '';
        }
        if ($phoneE164 === '') {
            return null;
        }

        $digits = RingostatPhoneFormatHelper::getDigits($phoneE164);
        if (strlen($digits) < 9) {
            return null;
        }

        $orderFrom = $order->date ?? null;
        if ($orderFrom === null || $orderFrom === '') {
            return null;
        }

        $orderFromStr = (string) $orderFrom;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $orderFromStr)) {
            $orderFromStr .= ' 00:00:00';
        }

        $ts = strtotime($orderFromStr);
        if ($ts === false) {
            return null;
        }

        return [
            'phone_like' => '%' . $digits . '%',
            'caller_filter' => mb_substr($digits, -10),
            'order_from_sql' => date('Y-m-d H:i:s', $ts),
        ];
    }

    private function enrichRingostatCallForOrderCard(object $call, string $rootUrl, string $callerFilter): void
    {
        $callerVal = ($call->direction ?? '') === 'in' ? ($call->caller ?? '') : ($call->callee ?? '');
        $calleeVal = ($call->direction ?? '') === 'in' ? ($call->callee ?? '') : ($call->caller ?? '');
        $call->display_caller = RingostatPhoneFormatHelper::formatDisplay($callerVal);
        $call->display_callee = RingostatPhoneFormatHelper::formatDisplay($calleeVal);
        $call->caller_tel = $callerVal;
        $call->callee_tel = $calleeVal;
        $call->is_phone_caller = RingostatPhoneFormatHelper::isPhone($callerVal);
        $call->is_phone_callee = RingostatPhoneFormatHelper::isPhone($calleeVal);

        $day = !empty($call->started_at) ? date('Y-m-d', strtotime((string) $call->started_at)) : '';
        $call->journal_url = $rootUrl . '/backend/index.php?controller=Sviat.Ringostat.RingostatCallsAdmin&date_from=' . rawurlencode($day) . '&date_to=' . rawurlencode($day) . '&caller=' . rawurlencode($callerFilter);
    }
}
