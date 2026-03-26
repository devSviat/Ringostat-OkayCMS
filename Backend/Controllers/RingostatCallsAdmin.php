<?php

namespace Okay\Modules\Sviat\Ringostat\Backend\Controllers;

use Okay\Admin\Controllers\IndexAdmin;
use Okay\Core\Database;
use Okay\Core\EntityFactory;
use Okay\Core\Phone;
use Okay\Core\QueryFactory;
use Okay\Core\Response;
use Okay\Core\ServiceLocator;
use Okay\Entities\OrdersEntity;
use Okay\Entities\UsersEntity;
use Okay\Modules\Sviat\Ringostat\Backend\Helpers\RingostatBackendHelper;
use Okay\Modules\Sviat\Ringostat\Entities\RingostatCallsEntity;
use Okay\Modules\Sviat\Ringostat\Helpers\RingostatHelper;
use Okay\Modules\Sviat\Ringostat\Helpers\RingostatPhoneFormatHelper;

/**
 * Журнал дзвінків Ringostat: список, синхронізація, проксі запису.
 */
class RingostatCallsAdmin extends IndexAdmin
{
    private const CONTROLLER_NAME = 'Sviat.Ringostat.RingostatCallsAdmin';
    private const CALLS_PER_PAGE = 25;
    private const ALLOWED_STATUS = ['PROPER', 'ANSWERED', 'VOICEMAIL', 'MISSED', 'NO ANSWER', 'FAILED', 'REPEATED', 'BUSY'];
    private const ALLOWED_DIRECTION = ['in', 'out'];
    private const ALLOWED_SORT = [
        'started_at_desc', 'started_at_asc', 'duration_desc', 'duration_asc',
        'caller_asc', 'caller_desc', 'direction_asc', 'direction_desc',
    ];

    /**
     * Сторінка журналу дзвінків.
     */
    public function fetch(EntityFactory $entityFactory): Response
    {
        /** @var RingostatCallsEntity $callsEntity */
        $callsEntity = $entityFactory->get(RingostatCallsEntity::class);

        $dateFrom = RingostatBackendHelper::sanitizeDateYmd($this->request->get('date_from', 'string', ''), '');
        $dateTo = RingostatBackendHelper::sanitizeDateYmd($this->request->get('date_to', 'string', ''), '');
        $caller = mb_substr($this->request->get('caller', 'string', ''), 0, 64);
        $statusFilter = $this->request->get('status', 'string', '');
        $directionFilter = $this->request->get('direction', 'string', '');
        $sort = $this->request->get('sort', 'string', 'started_at_desc');
        $pageParam = $this->request->get('page', 'string', '1');

        if (!in_array($sort, self::ALLOWED_SORT, true)) {
            $sort = 'started_at_desc';
        }
        if ($statusFilter !== '' && !in_array($statusFilter, self::ALLOWED_STATUS, true)) {
            $statusFilter = '';
        }
        if ($directionFilter !== '' && !in_array($directionFilter, self::ALLOWED_DIRECTION, true)) {
            $directionFilter = '';
        }

        $filter = $this->buildCallsFilter($dateFrom, $dateTo, $caller, $statusFilter, $directionFilter);

        $showAll = ($pageParam === 'all');
        if ($showAll) {
            $callsCount = $callsEntity->count($filter);
            $filter['limit'] = $callsCount > 0 ? $callsCount : 1;
            $filter['page'] = 1;
        } else {
            $page = max(1, (int) $pageParam);
            $filter['limit'] = self::CALLS_PER_PAGE;
            $filter['page'] = $page;
            $callsCount = $callsEntity->count($filter);
        }

        $filter['order'] = $sort;
        $calls = $callsEntity->find($filter);

        foreach ($calls as $call) {
            $callerVal = ($call->direction ?? '') === 'in' ? ($call->caller ?? '') : ($call->callee ?? '');
            $calleeVal = ($call->direction ?? '') === 'in' ? ($call->callee ?? '') : ($call->caller ?? '');
            $call->display_caller = RingostatPhoneFormatHelper::formatDisplay($callerVal);
            $call->display_callee = RingostatPhoneFormatHelper::formatDisplay($calleeVal);
            $call->caller_tel = $callerVal;
            $call->callee_tel = $calleeVal;
            $call->is_phone_caller = RingostatPhoneFormatHelper::isPhone($callerVal);
            $call->is_phone_callee = RingostatPhoneFormatHelper::isPhone($calleeVal);
            $call->orders = [];
            $call->user = null;
        }

        $this->attachOrdersAndUsersByCallerPhone($calls, $entityFactory);

        $pagesCount = $showAll ? 1 : max(1, (int) ceil($callsCount / self::CALLS_PER_PAGE));
        $currentPage = $showAll ? 1 : min($page ?? 1, $pagesCount);

        $rootUrl = $this->request->getRootUrl();
        $callsBaseUrl = $rootUrl . '/backend/index.php?controller=' . self::CONTROLLER_NAME;

        $this->design->assign('calls', $calls);
        $this->design->assign('calls_count', $callsCount);
        $this->design->assign('date_from', $dateFrom);
        $this->design->assign('date_to', $dateTo);
        $this->design->assign('caller_filter', $caller);
        $this->design->assign('status_filter', $statusFilter);
        $this->design->assign('direction_filter', $directionFilter);
        $this->design->assign('sort', $sort);
        $this->design->assign('pages_count', $pagesCount);
        $this->design->assign('current_page', $currentPage);
        $this->design->assign('ringostat_calls_base_url', $callsBaseUrl);
        $this->design->assign('root_url', $rootUrl);
        $this->design->assign('sync_imported', $this->request->get('sync_imported', 'integer', null));
        $this->design->assign('sync_updated', $this->request->get('sync_updated', 'integer', null));

        return $this->response->setContent($this->design->fetch('ringostat_calls.tpl'));
    }

    /**
     * Синхронізація дзвінків з API (POST). Редирект на журнал.
     */
    public function syncCalls(RingostatHelper $ringostatHelper): Response
    {
        $dateFrom = RingostatBackendHelper::sanitizeDateYmd(trim($this->request->post('date_from', 'string', '')), date('Y-m-d', strtotime('-30 days')));
        $dateTo = RingostatBackendHelper::sanitizeDateYmd(trim($this->request->post('date_to', 'string', '')), date('Y-m-d'));
        $forceFull = (bool) $this->request->post('force_full', 'integer', 0);

        $result = $ringostatHelper->syncCallsFromApi($dateFrom . ' 00:00:00', $dateTo . ' 23:59:59', $forceFull);

        $redirectUrl = $this->request->getRootUrl() . '/backend/index.php?controller=' . self::CONTROLLER_NAME;
        $redirectUrl .= '&date_from=' . urlencode($dateFrom) . '&date_to=' . urlencode($dateTo);

        if ($result['success']) {
            $this->postRedirectGet->storeMessageSuccess('sync_done');
            $redirectUrl .= '&sync_imported=' . (int) $result['imported'] . '&sync_updated=' . (int) $result['updated'];
        } else {
            $this->postRedirectGet->storeMessageError($result['error'] ?? 'Помилка синхронізації');
        }
        $this->postRedirectGet->redirect($redirectUrl);

        return $this->response;
    }

    /**
     * Проксі запису: редирект на record_url або 404.
     */
    public function record(EntityFactory $entityFactory): Response
    {
        $id = (int) $this->request->get('id', 'integer', 0);
        if ($id <= 0) {
            return $this->response->setContent('Not found', RESPONSE_TEXT)->setStatusCode(404);
        }

        /** @var RingostatCallsEntity $callsEntity */
        $callsEntity = $entityFactory->get(RingostatCallsEntity::class);
        $call = $callsEntity->get($id);
        if (!$call || empty($call->record_url)) {
            return $this->response->setContent('Record not found', RESPONSE_TEXT)->setStatusCode(404);
        }

        $redirectUrl = RingostatBackendHelper::validateRecordRedirectUrl($call->record_url);
        if ($redirectUrl === null) {
            return $this->response->setContent('Invalid record URL', RESPONSE_TEXT)->setStatusCode(400);
        }
        Response::redirectTo($redirectUrl, 302);
        return $this->response;
    }

    /**
     * Додає до кожного дзвінка orders та user за номером клієнта (caller_tel).
     * Один прохід по унікальних номерах — мінімум запитів до БД.
     */
    private function attachOrdersAndUsersByCallerPhone(iterable $calls, EntityFactory $entityFactory): void
    {
        // Збираємо унікальні номери в форматі E164 (як у БД).
        $phoneList = [];
        foreach ($calls as $call) {
            if (!$call->is_phone_caller || trim($call->caller_tel ?? '') === '') {
                continue;
            }
            $phone = Phone::toSave($call->caller_tel);
            if ($phone !== null && $phone !== '') {
                $phoneList[$phone] = true;
            }
        }
        $phoneList = array_keys($phoneList);

        /** @var OrdersEntity $ordersEntity */
        $ordersEntity = $entityFactory->get(OrdersEntity::class);
        /** @var UsersEntity $usersEntity */
        $usersEntity = $entityFactory->get(UsersEntity::class);

        $sl = ServiceLocator::getInstance();
        /** @var Database $db */
        $db = $sl->getService(Database::class);
        /** @var QueryFactory $queryFactory */
        $queryFactory = $sl->getService(QueryFactory::class);

        $ordersByPhone = [];
        $userByPhone = [];
        $ordersTable = OrdersEntity::getTable();
        $usersTable = UsersEntity::getTable();

        foreach ($phoneList as $phone) {
            // Замовлення з точним збігом E164.
            $orderSql = $queryFactory->newSqlQuery();
            $orderSql->setStatement(
                "SELECT id FROM {$ordersTable} WHERE phone = :phone ORDER BY id DESC LIMIT 30"
            )->bindValue('phone', $phone);
            $db->query($orderSql);
            $orderIds = $db->results('id');
            $ordersByPhone[$phone] = $orderIds !== [] ? $ordersEntity->find(['id' => $orderIds, 'order' => 'id DESC']) : [];

            // Один користувач з цим самим номером.
            $userSql = $queryFactory->newSqlQuery();
            $userSql->setStatement(
                "SELECT id FROM {$usersTable} WHERE phone = :phone LIMIT 1"
            )->bindValue('phone', $phone);
            $db->query($userSql);
            $userRow = $db->result();
            $userByPhone[$phone] = $userRow ? $usersEntity->get((int) $userRow->id) : null;
        }

        // Повертаємо orders та user у дзвінки.
        foreach ($calls as $call) {
            if (!$call->is_phone_caller || trim($call->caller_tel ?? '') === '') {
                continue;
            }
            $phone = Phone::toSave($call->caller_tel);
            if ($phone !== null && $phone !== '') {
                $call->orders = $ordersByPhone[$phone] ?? [];
                $call->user = $userByPhone[$phone] ?? null;
            }
        }
    }

    private function buildCallsFilter(string $dateFrom, string $dateTo, string $caller, string $status, string $direction): array
    {
        $filter = [];
        if ($dateFrom !== '') {
            $filter['started_at_from'] = $dateFrom . ' 00:00:00';
        }
        if ($dateTo !== '') {
            $filter['started_at_to'] = $dateTo . ' 23:59:59';
        }
        if ($caller !== '') {
            $filter['caller'] = $caller;
        }
        if ($status !== '') {
            $filter['status'] = $status;
        }
        if ($direction !== '') {
            $filter['direction'] = $direction;
        }
        return $filter;
    }
}
