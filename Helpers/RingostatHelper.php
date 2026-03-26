<?php

namespace Okay\Modules\Sviat\Ringostat\Helpers;

use Okay\Core\EntityFactory;
use Okay\Core\Phone;
use Okay\Core\Request;
use Okay\Entities\OrdersEntity;
use Okay\Modules\Sviat\Ringostat\Entities\RingostatCallsEntity;
use Okay\Modules\Sviat\Ringostat\Entities\RingostatCallbackQueueEntity;
use Okay\Modules\Sviat\Ringostat\Entities\RingostatContactsSyncEntity;

/**
 * Допоміжний клас для синхронізації клієнтів/замовлень з Ringostat.
 */
class RingostatHelper
{
    /** @var EntityFactory */
    private $entityFactory;

    /** @var RingostatApiClient */
    private $apiClient;

    /** @var \Okay\Core\Settings */
    private $settings;

    /** @var Request */
    private $request;

    /** @var RingostatSettingsHelper */
    private $settingsHelper;

    public function __construct(
        EntityFactory $entityFactory,
        RingostatApiClient $apiClient,
        \Okay\Core\Settings $settings,
        Request $request,
        RingostatSettingsHelper $settingsHelper
    ) {
        $this->entityFactory = $entityFactory;
        $this->apiClient = $apiClient;
        $this->settings = $settings;
        $this->request = $request;
        $this->settingsHelper = $settingsHelper;
    }

    /**
     * Останнє замовлення за номером телефону (для пропущеного дзвінка).
     */
    public function findLastOrderByPhone(string $phone): ?object
    {
        $cleaned = Phone::clear($phone);
        if ($cleaned === '') {
            return null;
        }
        /** @var OrdersEntity $ordersEntity */
        $ordersEntity = $this->entityFactory->get(OrdersEntity::class);
        $orders = $ordersEntity->find([
            'keyword' => $cleaned,
            'order' => 'id DESC',
            'limit' => 20,
        ]);
        foreach ($orders as $order) {
            if (Phone::clear($order->phone ?? '') === $cleaned) {
                return $order;
            }
        }
        return null;
    }

    public function syncContactFromOrder(object $order): array
    {
        $syncEnabled = $this->settingsHelper->getSyncContactsEnabled();
        if (!$syncEnabled) {
            return ['success' => false, 'error' => 'sync disabled'];
        }

        $this->apiClient->setCredentials(
            $this->settingsHelper->get('api_key') ?: $this->settings->get('sviat__ringostat__auth_key'),
            $this->settingsHelper->get('project_id') ?: $this->settings->get('sviat__ringostat__project_id')
        );

        if (!$this->apiClient->isConfigured()) {
            return ['success' => false, 'error' => 'Ringostat not configured'];
        }

        $phone = null;
        if (!empty($order->phone)) {
            $phone = Phone::toSave($order->phone);
            if ($phone === null || $phone === '') {
                $phone = Phone::toSave(trim($order->phone)) ?? trim($order->phone);
            }
        }
        if (empty($phone)) {
            return ['success' => false, 'error' => 'No phone in order'];
        }

        $nameFormat = $this->settingsHelper->getContactNameFormat();
        if ($nameFormat === RingostatSettingsHelper::CONTACT_NAME_FORMAT_ORDER_ID) {
            $fullName = '#' . $order->id;
        } elseif ($nameFormat === RingostatSettingsHelper::CONTACT_NAME_FORMAT_NAME_ORDER_ID) {
            $fullName = trim($order->name ?? '') . ' #' . $order->id;
            if ($fullName === ' #' . $order->id) {
                $fullName = 'Клієнт #' . $order->id;
            }
        } else {
            $fullName = trim(($order->name ?? '') . ' ' . ($order->last_name ?? ''));
            if ($fullName === '') {
                $fullName = 'Клієнт #' . $order->id;
            }
        }

        $projectId = (int) ($this->settingsHelper->get('project_id') ?: $this->settings->get('sviat__ringostat__project_id'));
        $origin = 'BrokenCRM';

        $contact = [
            'projectId' => $projectId,
            'fullName' => $fullName,
            'origin' => $origin,
            'externalId' => (string) $order->id,
            'leadId' => (string) $order->id,
            'responsible' => '0',
            'staffId' => null,
            'contactLink' => null,
            'leadLink' => null,
            'dealLink' => null,
            'googleClientId' => null,
            'contactDirections' => [
                ['name' => 'personal', 'type' => 'phone', 'value' => $phone],
            ],
            'organizations' => [],
        ];

        if (!empty($order->email)) {
            $contact['contactDirections'][] = [
                'name' => 'personal',
                'type' => 'email',
                'value' => $order->email,
            ];
        }

        $result = $this->apiClient->syncContacts([$contact]);
        if ($result['success']) {
            $this->upsertContactsSync($order->user_id ?? 0, $phone, $fullName, $order->id, (float) ($order->total_price ?? 0), 'success');
        } else {
            $this->upsertContactsSync($order->user_id ?? 0, $phone, $fullName, $order->id, (float) ($order->total_price ?? 0), 'error');
        }
        return $result;
    }

    /**
     * Оновлення/додавання запису в ringostat_contacts_sync.
     * Номер у полі phone зберігається в форматі E164.
     */
    public function upsertContactsSync(int $userId, string $phone, string $name, ?int $lastOrderId, float $lastOrderSum, string $syncStatus = 'success', ?string $ringostatContactId = null): void
    {
        $phone = trim($phone);
        $phoneForDb = $phone !== '' ? (Phone::toSave($phone) ?? $phone) : $phone;

        /** @var RingostatContactsSyncEntity $entity */
        $entity = $this->entityFactory->get(RingostatContactsSyncEntity::class);
        $existing = $entity->findOne(['phone' => $phoneForDb]);
        $now = date('Y-m-d H:i:s');
        $row = (object)[
            'user_id' => $userId ?: null,
            'phone' => $phoneForDb,
            'name' => $name,
            'last_order_id' => $lastOrderId,
            'last_order_sum' => $lastOrderSum,
            'synced_at' => $now,
            'sync_status' => $syncStatus,
            'ringostat_contact_id' => $ringostatContactId,
        ];
        if ($existing) {
            $entity->update($existing->id, $row);
        } else {
            $entity->add($row);
        }
    }

    public function syncContactFromUser(object $user): array
    {
        if (!$this->settingsHelper->getSyncContactsEnabled()) {
            return ['success' => false, 'error' => 'sync disabled'];
        }

        $this->apiClient->setCredentials(
            $this->settingsHelper->get('api_key') ?: $this->settings->get('sviat__ringostat__auth_key'),
            $this->settingsHelper->get('project_id') ?: $this->settings->get('sviat__ringostat__project_id')
        );

        if (!$this->apiClient->isConfigured()) {
            return ['success' => false, 'error' => 'Ringostat not configured'];
        }

        $phone = null;
        if (!empty($user->phone)) {
            $phone = Phone::toSave($user->phone);
            if ($phone === null || $phone === '') {
                $phone = Phone::toSave(trim($user->phone)) ?? trim($user->phone);
            }
        }
        $email = $user->email ?? null;
        if (empty($phone) && empty($email)) {
            return ['success' => false, 'error' => 'No phone or email'];
        }

        $fullName = trim(($user->name ?? '') . ' ' . ($user->last_name ?? ''));
        if ($fullName === '') {
            $fullName = 'Клієнт #' . $user->id;
        }

        $projectId = (int) $this->settings->get('sviat__ringostat__project_id');
        $contact = [
            'projectId' => $projectId,
            'fullName' => $fullName,
            'origin' => 'BrokenCRM',
            'externalId' => 'user_' . $user->id,
            'leadId' => null,
            'responsible' => '0',
            'staffId' => null,
            'contactLink' => null,
            'leadLink' => null,
            'dealLink' => null,
            'googleClientId' => null,
            'contactDirections' => [],
            'organizations' => [],
        ];

        if ($phone) {
            $contact['contactDirections'][] = ['name' => 'work', 'type' => 'phone', 'value' => $phone];
        }
        if ($email) {
            $contact['contactDirections'][] = ['name' => 'work', 'type' => 'email', 'value' => $email];
        }

        if (empty($contact['contactDirections'])) {
            return ['success' => false, 'error' => 'No directions'];
        }

        $result = $this->apiClient->syncContacts([$contact]);
        if ($result['success']) {
            $this->upsertContactsSync((int)$user->id, $phone ?? '', $fullName, null, 0, 'success');
        } else {
            $this->upsertContactsSync((int)$user->id, $phone ?? '', $fullName, null, 0, 'error');
        }
        return $result;
    }

    /** Журнал дзвінків API (credentials з налаштувань). @see https://ringostat.readme.io/reference/get_calls-list */
    public function getCallsList(array $params = []): array
    {
        $this->apiClient->setCredentials(
            $this->settingsHelper->get('api_key') ?: $this->settings->get('sviat__ringostat__auth_key'),
            $this->settingsHelper->get('project_id') ?: $this->settings->get('sviat__ringostat__project_id')
        );
        return $this->apiClient->getCallsList($params);
    }

    /** Callback: extension (вхідний номер проєкту) → destination. @see https://ringostat.readme.io/reference/post_callback-outward-call */
    public function callbackOutwardCall(string $extension, string $destination): array
    {
        $this->apiClient->setCredentials(
            $this->settingsHelper->get('api_key') ?: $this->settings->get('sviat__ringostat__auth_key'),
            $this->settingsHelper->get('project_id') ?: $this->settings->get('sviat__ringostat__project_id')
        );
        return $this->apiClient->callbackOutwardCall($extension, $destination);
    }

    /** Розширений callback (JSON-RPC Callback.external). @see https://ringostat.readme.io/reference/post_a-v2 */
    public function callbackExternal(array $params): array
    {
        $this->apiClient->setCredentials(
            $this->settingsHelper->get('api_key') ?: $this->settings->get('sviat__ringostat__auth_key'),
            $this->settingsHelper->get('project_id') ?: $this->settings->get('sviat__ringostat__project_id')
        );
        $params['projectId'] = $params['projectId'] ?? $this->settingsHelper->get('project_id') ?? $this->settings->get('sviat__ringostat__project_id');
        return $this->apiClient->callbackExternal($params);
    }

    /** SIP-акаунти проєкту Online. */
    public function getSipOnline(): array
    {
        $this->apiClient->setCredentials(
            $this->settingsHelper->get('api_key') ?: $this->settings->get('sviat__ringostat__auth_key'),
            $this->settingsHelper->get('project_id') ?: $this->settings->get('sviat__ringostat__project_id')
        );
        return $this->apiClient->getSipOnline();
    }

    /** SIP-акаунти, які зараз у розмові. */
    public function getSipSpeaking(): array
    {
        $this->apiClient->setCredentials(
            $this->settingsHelper->get('api_key') ?: $this->settings->get('sviat__ringostat__auth_key'),
            $this->settingsHelper->get('project_id') ?: $this->settings->get('sviat__ringostat__project_id')
        );
        return $this->apiClient->getSipSpeaking();
    }

    /** Відправка організацій у Ringostat (minicrm/organizations/sync). */
    public function syncOrganizations(array $organizations): array
    {
        $this->apiClient->setCredentials(
            $this->settingsHelper->get('api_key') ?: $this->settings->get('sviat__ringostat__auth_key'),
            $this->settingsHelper->get('project_id') ?: $this->settings->get('sviat__ringostat__project_id')
        );
        $projectId = (int) ($this->settingsHelper->get('project_id') ?: $this->settings->get('sviat__ringostat__project_id'));
        foreach ($organizations as &$org) {
            if (!isset($org['projectId'])) {
                $org['projectId'] = $projectId;
            }
        }
        unset($org);
        return $this->apiClient->syncOrganizations($organizations);
    }

    /** Синхронізація контактів (minicrm/contacts/sync). */
    public function syncContacts(array $contacts): array
    {
        $this->apiClient->setCredentials(
            $this->settingsHelper->get('api_key') ?: $this->settings->get('sviat__ringostat__auth_key'),
            $this->settingsHelper->get('project_id') ?: $this->settings->get('sviat__ringostat__project_id')
        );
        return $this->apiClient->syncContacts($contacts);
    }

    /**
     * Синхронізація дзвінків з API у sviat__ringostat_calls (по днях, макс 6500/запит).
     * Без forceFull — лише нові + оновлення IN PROGRESS/CALLING.
     * @return array{success: bool, error: ?string, imported: int, updated: int}
     */
    public function syncCallsFromApi(string $dateFrom, string $dateTo, bool $forceFull = false): array
    {
        $this->apiClient->setCredentials(
            $this->settingsHelper->get('api_key') ?: $this->settings->get('sviat__ringostat__auth_key'),
            $this->settingsHelper->get('project_id') ?: $this->settings->get('sviat__ringostat__project_id')
        );

        if (!$this->apiClient->isConfigured()) {
            return ['success' => false, 'error' => 'Ringostat не налаштовано', 'imported' => 0, 'updated' => 0];
        }

        /** @var RingostatCallsEntity $callsEntity */
        $callsEntity = $this->entityFactory->get(RingostatCallsEntity::class);
        /** @var RingostatCallbackQueueEntity $queueEntity */
        $queueEntity = $this->entityFactory->get(RingostatCallbackQueueEntity::class);

        if (!$forceFull) {
            $maxStartedAt = $callsEntity->getMaxStartedAt();
            if ($maxStartedAt !== null) {
                $fromTs = strtotime($maxStartedAt);
                $rangeStartTs = strtotime($dateFrom);
                if ($fromTs !== false && $rangeStartTs !== false) {
                    // Вікно «назад» 2 год від останнього дзвінка — щоб перезавантажити IN PROGRESS/CALLING і оновити статус; не раніше початку періоду
                    $fromTs = max($fromTs - 7200, $rangeStartTs);
                    $dateFrom = date('Y-m-d H:i:s', $fromTs);
                }
                if ($dateFrom >= $dateTo) {
                    return ['success' => true, 'error' => null, 'imported' => 0, 'updated' => 0];
                }
            }
        }

        $chunks = $this->splitDateRangeForApi($dateFrom, $dateTo);
        $allCalls = [];

        foreach ($chunks as [$from, $to]) {
            $result = $this->apiClient->getCallsList([
                'from' => $from,
                'to' => $to,
                'fields' => 'uniqueid,calldate,caller,caller_number,dst,disposition,duration,billsec,call_type,waittime,recording,recording_wav,employee_number,employee_fio,department,call_card,utm_source,utm_medium,utm_campaign',
                'merge' => 0,
                'order' => 'calldate desc',
            ]);

            if (!$result['success']) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Помилка API',
                    'imported' => 0,
                    'updated' => 0,
                ];
            }

            $calls = $result['calls'];
            if (is_array($calls)) {
                $allCalls = array_merge($allCalls, $calls);
            }
        }

        $imported = 0;
        $updated = 0;
        $now = date('Y-m-d H:i:s');

        foreach ($allCalls as $row) {
            $row = is_array($row) ? (object) $row : (is_object($row) ? $row : (object) []);
            $callId = $row->uniqueid ?? $row->id ?? null;
            if (empty($callId)) {
                $callId = md5(($row->calldate ?? '') . ($row->caller ?? '') . ($row->dst ?? '') . ($row->call_type ?? $row->type ?? ''));
            }
            $callId = (string) $callId;

            $calldate = $row->calldate ?? $row->date ?? $now;
            if (is_string($calldate) && preg_match('/^\d{4}-\d{2}-\d{2}/', $calldate)) {
                $ts = strtotime($calldate);
                $calldate = $ts !== false ? date('Y-m-d H:i:s', $ts) : $now;
            } elseif (!empty($calldate)) {
                $calldate = date('Y-m-d H:i:s', is_numeric($calldate) ? (int) $calldate : strtotime($calldate));
            } else {
                $calldate = $now;
            }

            $existing = $callsEntity->findOne(['ringostat_call_id' => $callId]);
            $existingIsActive = $existing && in_array(strtoupper(trim($existing->status ?? '')), ['IN PROGRESS', 'IN_PROGRESS', 'CALLING'], true);
            if ($existing && !$existingIsActive) {
                continue;
            }

            $data = $this->mapApiCallRowToDbData($row, $callId, $calldate, $now, is_object($existing) ? $existing : null);

            if (is_object($existing)) {
                $callsEntity->update($existing->id, $data);
                $updated++;
            } else {
                $callsEntity->add($data);
                $imported++;
            }

            $this->updateCallbackQueueForCall($queueEntity, $data);
        }

        return ['success' => true, 'error' => null, 'imported' => $imported, 'updated' => $updated];
    }

    /** Черга передзвону: пропущений (in+NO ANSWER/VOICEMAIL) → +1; PROPER/ANSWERED → видалити. */
    private function updateCallbackQueueForCall(RingostatCallbackQueueEntity $queueEntity, object $callData): void
    {
        $direction = $callData->direction ?? '';
        $status = trim($callData->status ?? '');
        $caller = trim($callData->caller ?? '');
        $callee = trim($callData->callee ?? '');

        $isMissed = $direction === 'in' && in_array($status, ['NO ANSWER', 'VOICEMAIL'], true);
        $isAnswered = in_array($status, ['PROPER', 'ANSWERED'], true);

        if ($isMissed) {
            $clientPhone = $caller;
        } elseif ($isAnswered) {
            $clientPhone = $direction === 'in' ? $caller : $callee;
        } else {
            return;
        }

        $normalized = preg_replace('/\D/', '', $clientPhone);
        if ($normalized === '' || strlen($normalized) < 9 || preg_match('/[a-zA-Z]/', $clientPhone)) {
            return;
        }

        $phoneForDb = Phone::toSave($clientPhone);
        if ($phoneForDb === null) {
            return;
        }

        if ($isMissed) {
            $existing = $queueEntity->findOne(['phone' => $phoneForDb]);
            $lastMissedAt = $callData->started_at ?? date('Y-m-d H:i:s');
            if ($existing) {
                $queueEntity->update($existing->id, (object) [
                    'missed_count' => (int) $existing->missed_count + 1,
                    'last_missed_at' => $lastMissedAt,
                ]);
            } else {
                $queueEntity->add((object) [
                    'phone' => $phoneForDb,
                    'missed_count' => 1,
                    'last_missed_at' => $lastMissedAt,
                    'processed' => 0,
                ]);
            }
            return;
        }

        if ($isAnswered) {
            $existing = $queueEntity->findOne(['phone' => $phoneForDb]);
            if ($existing) {
                $queueEntity->delete($existing->id);
            }
        }
    }

    /** Маппінг рядка API calls/list → об'єкт для БД (disposition→status, call_type→direction). */
    private function mapApiCallRowToDbData(object $row, string $callId, string $calldate, string $now, ?object $existing): object
    {
        $direction = strtolower(trim($row->call_type ?? $row->type ?? ''));
        if ($direction === 'inbound' || $direction === 'in') {
            $direction = 'in';
        } elseif ($direction === 'outbound' || $direction === 'out') {
            $direction = 'out';
        } else {
            $direction = 'in';
        }

        // Статус тільки з disposition — який отримали з API, такий і записуємо (без fallback на status).
        $status = isset($row->disposition) ? trim((string) $row->disposition) : '';

        $recordUrl = trim($row->recording ?? $row->recording_wav ?? $row->record_link ?? $row->record_url ?? '') ?: null;
        $callerRaw = trim($row->caller_number ?? $row->caller_numb ?? $row->caller ?? '');
        if ($callerRaw === '' && !empty($row->caller) && preg_match('/[\d+][\d\s\-]+/', (string) $row->caller, $m)) {
            $callerRaw = preg_replace('/\D/', '', $m[0]);
        }
        $calleeRaw = trim($row->dst ?? $row->destination ?? '');

        $empNum = isset($row->employee_number) ? (int) $row->employee_number : null;
        $managerId = ($empNum !== null && $empNum > 0) ? $empNum : null;
        $employeeFio = trim($row->employee_fio ?? '') ?: null;

        $waittime = isset($row->waittime) ? (int) $row->waittime : null;
        if ($waittime !== null && $waittime < 0) {
            $waittime = null;
        }
        $billsec = isset($row->billsec) ? (int) $row->billsec : null;
        $billsec = ($billsec !== null && $billsec >= 0) ? $billsec : null;
        $department = trim($row->department ?? '') ?: null;
        $callCard = trim($row->call_card ?? '') ?: null;

        return (object) [
            'ringostat_call_id' => $callId,
            'direction' => $direction,
            'status' => $status,
            'caller' => $this->normalizeCallerCalleeForDb($callerRaw),
            'callee' => $this->normalizeCallerCalleeForDb($calleeRaw),
            'duration' => (int) ($row->billsec ?? $row->duration ?? 0),
            'waittime' => $waittime,
            'billsec' => $billsec,
            'department' => $department,
            'call_card' => $callCard,
            'record_url' => $recordUrl,
            'manager_id' => $managerId,
            'employee_fio' => $employeeFio,
            'utm_source' => trim($row->utm_source ?? '') ?: null,
            'utm_medium' => trim($row->utm_medium ?? '') ?: null,
            'utm_campaign' => trim($row->utm_campaign ?? '') ?: null,
            'started_at' => $calldate,
            'created_at' => $existing ? $existing->created_at : $now,
        ];
    }

    /** Період по днях [from, to] (Y-m-d H:i:s) для обмеження API. */
    private function splitDateRangeForApi(string $dateFrom, string $dateTo): array
    {
        $fromTs = strtotime($dateFrom);
        $toTs = strtotime($dateTo);
        if ($fromTs === false || $toTs === false || $fromTs >= $toTs) {
            return [[$dateFrom, $dateTo]];
        }

        $chunks = [];
        $current = $fromTs;
        $oneDay = 86400;

        while ($current < $toTs) {
            $chunkEnd = min($current + $oneDay, $toTs);
            $chunks[] = [
                date('Y-m-d H:i:s', $current),
                date('Y-m-d H:i:s', $chunkEnd),
            ];
            $current = $chunkEnd;
        }

        return $chunks;
    }

    /** caller/callee для БД: повні номери → E164, внутрішні/SIP — як є. */
    private function normalizeCallerCalleeForDb(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (preg_match('/[a-zA-Z]/', $value)) {
            return $value;
        }
        $digits = preg_replace('/\D/', '', $value);
        if ($digits === '' || strlen($digits) < 9) {
            return $value;
        }
        return Phone::toSave($value) ?? $value;
    }

}
