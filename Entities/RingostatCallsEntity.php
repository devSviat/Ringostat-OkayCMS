<?php

namespace Okay\Modules\Sviat\Ringostat\Entities;

use Okay\Core\Entity\Entity;

/**
 * Журнал дзвінків Ringostat (синхронізація з API calls/list).
 * Маппінг: uniqueid→ringostat_call_id, calldate→started_at, call_type→direction,
 * disposition→status, caller_number/dst→caller/callee, recording→record_url, employee_*→manager.
 */
class RingostatCallsEntity extends Entity
{
    protected static $fields = [
        'id',
        'ringostat_call_id',
        'direction',
        'status',
        'caller',
        'callee',
        'duration',
        'waittime',
        'billsec',
        'department',
        'call_card',
        'record_url',
        'manager_id',
        'employee_fio',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'started_at',
        'created_at',
    ];

    protected static $table = 'sviat__ringostat_calls';
    protected static $tableAlias = 'rsc';

    protected static $defaultOrderFields = [
        'started_at DESC',
        'id DESC',
    ];

    protected function filter__started_at_from($value): void
    {
        if ($value === '' || $value === null) {
            return;
        }
        $this->select->where('rsc.started_at >= :started_at_from')->bindValue('started_at_from', $value);
    }

    protected function filter__started_at_to($value): void
    {
        if ($value === '' || $value === null) {
            return;
        }
        $this->select->where('rsc.started_at <= :started_at_to')->bindValue('started_at_to', $value);
    }

    protected function filter__caller($value): void
    {
        if ($value === '' || $value === null) {
            return;
        }
        $this->select->where('rsc.caller LIKE :caller_filter')->bindValue('caller_filter', '%' . $value . '%');
    }

    protected function filter__status($value): void
    {
        if ($value === '' || $value === null) {
            return;
        }
        // Пропущено: вхідні (in) з NO ANSWER або будь-які VOICEMAIL
        if ($value === 'MISSED') {
            $this->select->where('((rsc.direction = :filter_missed_dir AND rsc.status = :filter_no_answer) OR (rsc.status = :filter_voicemail))')
                ->bindValue('filter_missed_dir', 'in')
                ->bindValue('filter_no_answer', 'NO ANSWER')
                ->bindValue('filter_voicemail', 'VOICEMAIL');
            return;
        }
        // Без відповіді: лише вихідні (out) з NO ANSWER
        if ($value === 'NO ANSWER') {
            $this->select->where('rsc.status = :filter_status_no_answer AND rsc.direction = :filter_no_answer_dir')
                ->bindValue('filter_status_no_answer', 'NO ANSWER')
                ->bindValue('filter_no_answer_dir', 'out');
            return;
        }
        $this->select->where('rsc.status = :filter_status')->bindValue('filter_status', $value);
    }

    protected function filter__direction($value): void
    {
        if ($value === '' || $value === null) {
            return;
        }
        $this->select->where('rsc.direction = :filter_direction')->bindValue('filter_direction', $value);
    }

    protected function filter__order($value): void
    {
        if ($value === '' || $value === null) {
            return;
        }
        $this->order($value);
    }

    /**
     * Остання (максимальна) дата/час дзвінка в БД (для інкрементальної синхронізації).
     */
    public function getMaxStartedAt(): ?string
    {
        $list = $this->find(['limit' => 1]);
        $first = reset($list);

        return $first && !empty($first->started_at) ? $first->started_at : null;
    }

    /**
     * Дзвінки з номером клієнта (як у журналі: in→caller, out→callee), не раніше дати/часу замовлення.
     * Один запит SELECT * — без другого round-trip через find() по id.
     *
     * @return array<int, object>
     */
    public function findCallsForOrder(string $phoneLike, string $orderDateTime): array
    {
        $table = self::getTable();
        $sql = "SELECT rsc.* FROM `{$table}` AS rsc
            WHERE rsc.started_at >= :order_from
            AND (
                (rsc.direction = 'in' AND rsc.caller LIKE :phone_like)
                OR (rsc.direction = 'out' AND rsc.callee LIKE :phone_like)
            )
            ORDER BY rsc.started_at DESC
            LIMIT 50";

        $sqlQuery = $this->queryFactory->newSqlQuery();
        $sqlQuery->setStatement($sql)
            ->bindValue('order_from', $orderDateTime)
            ->bindValue('phone_like', $phoneLike);
        $this->db->query($sqlQuery);

        return $this->db->results() ?: [];
    }
}
