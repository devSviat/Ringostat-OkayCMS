<?php

namespace Okay\Modules\Sviat\Ringostat\Entities;

use Okay\Core\Entity\Entity;

/**
 * Черга передзвону — похідний список із журналу дзвінків, а не окремий стан.
 *
 * Номер у списку, якщо по ньому є пропущений вхідний (in + NO ANSWER/VOICEMAIL)
 * і після нього немає успішного дзвінка (PROPER/ANSWERED) у будь-якому напрямку.
 * Ця таблиця зберігає лише відмітку оператора: processed_until — час останнього
 * пропущеного, який уже відпрацьовано. Новіший пропущений дзвінок автоматично
 * повертає номер у список.
 */
class RingostatCallbackQueueEntity extends Entity
{
    protected static $fields = [
        'id',
        'phone',
        'missed_count',
        'last_missed_at',
        'processed',
        'processed_until',
    ];

    protected static $table = 'sviat__ringostat_callback_queue';
    protected static $tableAlias = 'rscq';

    protected static $defaultOrderFields = [
        'last_missed_at DESC',
        'id DESC',
    ];

    /**
     * Пропущений вхідний: клієнт дзвонив і не поговорив.
     * BUSY тут — не «зайнято в клієнта», а обрив із черги очікування: у всіх таких
     * дзвінків waittime 24–58 с, billsec 0 і жодного менеджера. Для передзвону це
     * те саме, що NO ANSWER.
     */
    private const MISSED_STATUSES = ['NO ANSWER', 'VOICEMAIL', 'BUSY'];

    /** Статус, у якого буває запис голосової пошти. */
    private const VOICEMAIL_STATUS = 'VOICEMAIL';

    /** Успішний дзвінок: розмова відбулась, передзвонювати не треба. */
    private const ANSWERED_STATUSES = ['PROPER', 'ANSWERED'];

    /**
     * Номери, яким треба передзвонити, від найсвіжішого пропущеного.
     *
     * @param int|null $limit скільки рядків віддати; null — усі
     * @param int $page номер сторінки, рахується від 1
     * @return array<int, object> phone, missed_count, last_missed_at, voicemail_record_url
     */
    public function findCallbackQueue(?int $limit = null, int $page = 1): array
    {
        // LIMIT/OFFSET підставляються числами, а не плейсхолдерами: MySQL не
        // приймає прив'язку в LIMIT при справжніх prepared statements.
        $paging = '';
        if ($limit !== null && $limit > 0) {
            $paging = ' LIMIT ' . $limit . ' OFFSET ' . (max(1, $page) - 1) * $limit;
        }

        $sql = $this->queryFactory->newSqlQuery();
        $sql->setStatement(
            'SELECT m.phone, m.missed_count, m.last_missed_at, vm.record_url AS voicemail_record_url'
            . $this->callbackQueueFromSql(true)
            . ' ORDER BY m.last_missed_at DESC'
            . $paging
        );
        $this->bindCallbackQueueValues($sql, true);

        if ($this->db->query($sql) !== true) {
            return [];
        }

        return $this->db->results() ?: [];
    }

    /**
     * Скільки номерів чекають на передзвін (для бейджа в меню).
     * Без JOIN голосової пошти: для підрахунку він зайвий, а метод висить на
     * evensCounters, тобто виконується на кожній сторінці адмінки.
     */
    public function countCallbackQueue(): int
    {
        $sql = $this->queryFactory->newSqlQuery();
        $sql->setStatement('SELECT COUNT(*) AS count FROM (SELECT m.phone' . $this->callbackQueueFromSql(false) . ') q');
        $this->bindCallbackQueueValues($sql, false);

        if ($this->db->query($sql) !== true) {
            return 0;
        }

        return (int) $this->db->result('count');
    }

    /**
     * Позначити номер відпрацьованим до вказаного пропущеного включно.
     * Пізніший пропущений дзвінок поверне номер у список сам.
     */
    public function markProcessed(string $phone, string $lastMissedAt): void
    {
        $existing = $this->findOne(['phone' => $phone]);
        $row = (object) [
            'phone' => $phone,
            'processed' => 1,
            'processed_until' => $lastMissedAt,
            'last_missed_at' => $lastMissedAt,
        ];

        if (is_object($existing)) {
            $this->update($existing->id, $row);
        } else {
            $this->add($row);
        }
    }

    /**
     * FROM/JOIN/WHERE спільні для списку й підрахунку — умова відбору мусить бути
     * одна, інакше бейдж у меню і сторінка знову розійдуться.
     *
     * Успішні беруться окремою derived-таблицею, а не корельованим підзапитом:
     * на 9k дзвінків це 18 мс замість 1690 мс при тому ж результаті.
     */
    private function callbackQueueFromSql(bool $withVoicemail): string
    {
        $calls = RingostatCallsEntity::getTable();
        $queue = self::getTable();
        $missed = implode(', ', self::placeholders('missed', self::MISSED_STATUSES));
        $answered = implode(', ', self::placeholders('answered', self::ANSWERED_STATUSES));
        $voicemailJoin = $withVoicemail ? "
            LEFT JOIN (
                SELECT v.caller AS phone,
                       SUBSTRING_INDEX(GROUP_CONCAT(v.record_url ORDER BY v.started_at DESC SEPARATOR 0x1f), 0x1f, 1) AS record_url
                FROM `{$calls}` v
                WHERE v.direction = 'in'
                  AND v.status = :voicemail_status
                  AND v.record_url IS NOT NULL
                  AND v.record_url <> ''
                GROUP BY v.caller
            ) vm ON vm.phone = m.phone" : '';

        return "
            FROM (
                SELECT c.caller AS phone, COUNT(*) AS missed_count, MAX(c.started_at) AS last_missed_at
                FROM `{$calls}` c
                WHERE c.direction = 'in'
                  AND c.status IN ({$missed})
                  AND c.caller <> ''
                  AND c.caller NOT REGEXP '[A-Za-z]'
                  AND CHAR_LENGTH(c.caller) >= 9
                GROUP BY c.caller
            ) m
            LEFT JOIN (
                SELECT IF(a.direction = 'in', a.caller, a.callee) AS phone, MAX(a.started_at) AS last_answered_at
                FROM `{$calls}` a
                WHERE a.status IN ({$answered})
                GROUP BY IF(a.direction = 'in', a.caller, a.callee)
            ) ans ON ans.phone = m.phone{$voicemailJoin}
            LEFT JOIN `{$queue}` q ON q.phone = m.phone
            WHERE (ans.last_answered_at IS NULL OR ans.last_answered_at <= m.last_missed_at)
              AND (q.processed_until IS NULL OR q.processed_until < m.last_missed_at)
        ";
    }

    /**
     * Плейсхолдери під список статусів. Імена генеруються з того самого масиву,
     * що й прив'язки, тож новий статус додається в одному місці — у константі.
     *
     * @return string[]
     */
    private static function placeholders(string $prefix, array $statuses): array
    {
        return array_map(static fn(int $i): string => ':' . $prefix . '_' . $i, array_keys($statuses));
    }

    private function bindCallbackQueueValues($sql, bool $withVoicemail): void
    {
        foreach ([['missed', self::MISSED_STATUSES], ['answered', self::ANSWERED_STATUSES]] as [$prefix, $statuses]) {
            foreach ($statuses as $i => $status) {
                $sql->bindValue($prefix . '_' . $i, $status);
            }
        }
        if ($withVoicemail) {
            $sql->bindValue('voicemail_status', self::VOICEMAIL_STATUS);
        }
    }
}
