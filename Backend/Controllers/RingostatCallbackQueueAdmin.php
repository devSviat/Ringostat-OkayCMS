<?php

namespace Okay\Modules\Sviat\Ringostat\Backend\Controllers;

use Okay\Admin\Controllers\IndexAdmin;
use Okay\Core\EntityFactory;
use Okay\Core\Response;
use Okay\Modules\Sviat\Ringostat\Backend\Helpers\RingostatBackendHelper;
use Okay\Modules\Sviat\Ringostat\Entities\RingostatCallbackQueueEntity;
use Okay\Modules\Sviat\Ringostat\Helpers\RingostatPhoneFormatHelper;

/**
 * Черга передзвону: номери з пропущеними вхідними (in + NO ANSWER/VOICEMAIL),
 * яким ще не передзвонили. Список рахується з журналу дзвінків, ключ рядка — телефон.
 */
class RingostatCallbackQueueAdmin extends IndexAdmin
{
    private const CONTROLLER_NAME = 'Sviat.Ringostat.RingostatCallbackQueueAdmin';
    private const ITEMS_PER_PAGE = 50;

    public function fetch(EntityFactory $entityFactory): Response
    {
        /** @var RingostatCallbackQueueEntity $queueEntity */
        $queueEntity = $entityFactory->get(RingostatCallbackQueueEntity::class);

        if ($this->request->method('post')) {
            $this->handleBulkActions($queueEntity);
            $this->postRedirectGet->redirect($this->listUrl());
            return $this->response;
        }

        $total = $queueEntity->countCallbackQueue();
        $showAll = $this->request->get('page') === 'all';
        $pagesCount = $showAll ? 1 : max(1, (int) ceil($total / self::ITEMS_PER_PAGE));
        $page = $showAll ? 1 : min(max(1, $this->request->get('page', 'integer')), $pagesCount);

        $items = $queueEntity->findCallbackQueue($showAll ? null : self::ITEMS_PER_PAGE, $page);
        foreach ($items as $item) {
            $item->display_phone = RingostatPhoneFormatHelper::formatDisplay($item->phone);
        }

        $rootUrl = $this->request->getRootUrl();

        $this->design->assign('queue_items', $items);
        $this->design->assign('queue_total', $total);
        $this->design->assign('pages_count', $pagesCount);
        $this->design->assign('current_page', $page);
        $this->design->assign('root_url', $rootUrl);
        $this->design->assign('ringostat_calls_base_url', $rootUrl . '/backend/index.php?controller=Sviat.Ringostat.RingostatCallsAdmin');

        return $this->response->setContent($this->design->fetch('ringostat_callback_queue.tpl'));
    }

    /** Позначити один номер обробленим (POST). */
    public function markProcessed(EntityFactory $entityFactory): Response
    {
        /** @var RingostatCallbackQueueEntity $queueEntity */
        $queueEntity = $entityFactory->get(RingostatCallbackQueueEntity::class);

        // Без фільтра 'string' у Request::post: він вирізає все, крім літер, цифр,
        // пробілів і _-.% — тобто з'їдає «+» у номері E164 і «:» у часі. Валідація
        // нижче строгіша за той фільтр.
        $phone = RingostatBackendHelper::sanitizePhone((string) $this->request->post('phone'));
        if ($phone !== null) {
            $this->markPhonesProcessed($queueEntity, [$phone => (string) $this->request->post('last_missed_at')]);
        }

        $this->postRedirectGet->redirect($this->listUrl());
        return $this->response;
    }

    /**
     * Адреса списку з тією ж сторінкою, з якої прийшов пост — після позначення
     * оператор має лишитись там, де був, а не поїхати на першу сторінку.
     * Якщо сторінка зникла (усе на ній відпрацьовано), fetch() підріже номер до
     * наявної кількості сторінок.
     */
    private function listUrl(): string
    {
        $url = $this->request->getRootUrl() . '/backend/index.php?controller=' . self::CONTROLLER_NAME;

        if ($this->request->get('page') === 'all') {
            return $url . '&page=all';
        }

        $page = $this->request->get('page', 'integer');

        return $page > 1 ? $url . '&page=' . $page : $url;
    }

    /** Масове позначення обробленими (check + action) та поодинокі кнопки в рядках. */
    private function handleBulkActions(RingostatCallbackQueueEntity $queueEntity): void
    {
        $lastMissedPosted = (array) $this->request->post('last_missed');
        $requested = [];

        $single = $this->request->post('single_action');
        if (is_array($single)) {
            foreach ($single as $phone => $action) {
                if ($action === 'mark_processed' && ($phone = RingostatBackendHelper::sanitizePhone((string) $phone)) !== null) {
                    $requested[$phone] = (string) ($lastMissedPosted[$phone] ?? '');
                }
            }
        }

        $check = (array) $this->request->post('check');
        if (!empty($check) && $this->request->post('action') === 'mark_processed') {
            foreach ($check as $phone) {
                if (($phone = RingostatBackendHelper::sanitizePhone((string) $phone)) !== null) {
                    $requested[$phone] = (string) ($lastMissedPosted[$phone] ?? '');
                }
            }
        }

        $this->markPhonesProcessed($queueEntity, $requested);
    }

    /**
     * Позначає номери обробленими до того пропущеного, який бачив оператор.
     * Час із форми обрізається реальним станом БД, щоб постом не можна було
     * «загасити» номер наперед — новіший пропущений дзвінок мусить його повернути.
     *
     * @param array<string, string> $requested phone => last_missed_at із форми
     */
    private function markPhonesProcessed(RingostatCallbackQueueEntity $queueEntity, array $requested): void
    {
        if (empty($requested)) {
            return;
        }

        $actualLastMissed = [];
        foreach ($queueEntity->findCallbackQueue() as $item) {
            $actualLastMissed[$item->phone] = $item->last_missed_at;
        }

        foreach ($requested as $phone => $postedLastMissed) {
            if (!isset($actualLastMissed[$phone])) {
                continue;
            }
            // Порівнюємо і зберігаємо обрізане значення. Інакше " 9999-01-01 00:00:00"
            // проходило валідацію (вона тримить), а в порівнянні рядків пробіл менший
            // за цифру — і час із форми вигравав у реального, глушачи номер назавжди.
            $posted = trim($postedLastMissed);
            $actual = $actualLastMissed[$phone];
            $lastMissedAt = RingostatBackendHelper::isDbDateTime($posted) && $posted < $actual ? $posted : $actual;
            $queueEntity->markProcessed($phone, $lastMissedAt);
        }
    }
}
