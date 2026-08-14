<?php

namespace Modules\Sviat\Ringostat;

use Okay\Core\QueryFactory;
use Okay\Core\QueryFactory\SqlQuery;
use Okay\Modules\Sviat\Ringostat\Entities\RingostatCallbackQueueEntity;
use PHPUnit\Framework\TestCase;

/**
 * Список «Передзвонити» рахується з журналу дзвінків одним запитом. Раніше це
 * був окремий стан у таблиці, який ішов урозріз із журналом: номер міг зникнути
 * зі списку через те, що старіший успішний дзвінок оброблявся після новішого
 * пропущеного.
 */
class RingostatCallbackQueueEntityTest extends TestCase
{
    public function testEmptyResultIsAnArrayAndNotFalse(): void
    {
        // Database::results() віддає [] і тоді, коли запит упав: помилки SQL лише
        // логуються. false звідси доїхав би до {count($queue_items)} у шаблоні.
        $entity = $this->entityReturning([]);

        $this->assertSame([], $entity->findCallbackQueue());
    }

    public function testRowsFromDatabaseArePassedThroughUnchanged(): void
    {
        $rows = [
            (object) ['phone' => '+380634250707', 'missed_count' => '2', 'last_missed_at' => '2026-08-12 13:58:50', 'voicemail_record_url' => null],
            (object) ['phone' => '+380916103823', 'missed_count' => '1', 'last_missed_at' => '2026-08-12 20:39:17', 'voicemail_record_url' => 'https://app.ringostat.com/recordings/x.wav'],
        ];

        $this->assertSame($rows, $this->entityReturning($rows)->findCallbackQueue());
    }

    public function testCountIsAnInt(): void
    {
        $entity = $this->entityReturning([], '294');

        $this->assertSame(294, $entity->countCallbackQueue());
    }

    /**
     * Один і той самий іменований плейсхолдер двічі в запиті PDO не приймає.
     * VOICEMAIL потрібен і для групування пропущених, і для пошуку голосової
     * пошти, тож імена мусять бути різні, а прив'язані — всі.
     */
    public function testEveryPlaceholderInBothStatementsIsBoundExactlyOnce(): void
    {
        foreach (['findCallbackQueue', 'countCallbackQueue'] as $method) {
            $entity = $this->entityReturning([]);
            $entity->$method();

            $query = $this->lastQuery;
            preg_match_all('/:([a-z0-9_]+)/', $query->getStatement(), $matches);
            $placeholders = $matches[1];
            $bound = array_keys($query->getBindValues());

            $this->assertNotEmpty($placeholders, "{$method}: статуси підставляються через плейсхолдери, а не літералами");
            $this->assertSame($placeholders, array_unique($placeholders), "{$method}: жоден плейсхолдер не повторюється");
            $this->assertSame([], array_diff($placeholders, $bound), "{$method}: усі плейсхолдери прив'язані");
            $this->assertSame([], array_diff($bound, $placeholders), "{$method}: немає зайвих прив'язок");
        }
    }

    /**
     * Сторінку рахуємо від 1, тож зсув третьої по 50 — це 100, а не 150.
     */
    public function testPagingIsAppendedAsLimitAndOffset(): void
    {
        $entity = $this->entityReturning([]);
        $entity->findCallbackQueue(50, 3);

        $this->assertStringContainsString('LIMIT 50 OFFSET 100', $this->lastQuery->getStatement());
    }

    public function testFirstPageHasNoOffset(): void
    {
        $entity = $this->entityReturning([]);
        $entity->findCallbackQueue(50, 1);

        $this->assertStringContainsString('LIMIT 50 OFFSET 0', $this->lastQuery->getStatement());
    }

    /**
     * Позначення обробленим звіряє номер із повним списком, а не зі сторінкою —
     * інакше не можна було б відпрацювати номер із будь-якої сторінки, крім першої.
     */
    public function testWithoutLimitTheStatementHasNoLimitClause(): void
    {
        $entity = $this->entityReturning([]);
        $entity->findCallbackQueue();

        $this->assertStringNotContainsString('LIMIT', $this->lastQuery->getStatement());
    }

    /**
     * BUSY на вхідному — це обрив із черги очікування (waittime 24–58 с, billsec 0,
     * без менеджера), тобто клієнт не поговорив. Разом із NO ANSWER і VOICEMAIL
     * це повний набір «не додзвонився».
     */
    public function testMissedAndAnsweredStatusesAreBound(): void
    {
        $entity = $this->entityReturning([]);
        $entity->findCallbackQueue();

        $bound = array_values($this->lastQuery->getBindValues());

        foreach (['NO ANSWER', 'VOICEMAIL', 'BUSY', 'PROPER', 'ANSWERED'] as $status) {
            $this->assertContains($status, $bound);
        }
    }

    /**
     * Бейдж у меню і сторінка мусять відбирати номери за однією умовою, інакше
     * вони знову розійдуться, як 306 проти 0. Підрахунок навмисно не тягне JOIN
     * голосової пошти (він на кожній сторінці адмінки), але сам відбір — той самий.
     */
    public function testCountSelectsByTheSameConditionsAsTheList(): void
    {
        $decisive = [
            'ans.last_answered_at IS NULL OR ans.last_answered_at <= m.last_missed_at',
            'q.processed_until IS NULL OR q.processed_until < m.last_missed_at',
        ];

        $list = $this->entityReturning([]);
        $list->findCallbackQueue();
        $listSql = $this->lastQuery->getStatement();
        $listStatuses = $this->boundStatuses();

        $count = $this->entityReturning([]);
        $count->countCallbackQueue();
        $countSql = $this->lastQuery->getStatement();
        $countStatuses = $this->boundStatuses();

        foreach ($decisive as $condition) {
            $this->assertStringContainsString($condition, $listSql);
            $this->assertStringContainsString($condition, $countSql);
        }

        $this->assertSame($listStatuses, $countStatuses, 'обидва запити відбирають за тими самими статусами');
    }

    /** Статуси відбору без службового VOICEMAIL для запису голосової пошти. */
    private function boundStatuses(): array
    {
        $values = $this->lastQuery->getBindValues();
        unset($values['voicemail_status']);
        sort($values);

        return $values;
    }

    private ?SqlQuery $lastQuery = null;

    private function entityReturning(array $rows, string $count = '0'): RingostatCallbackQueueEntity
    {
        $entity = (new \ReflectionClass(RingostatCallbackQueueEntity::class))->newInstanceWithoutConstructor();

        $queryFactory = $this->createStub(QueryFactory::class);
        $queryFactory->method('newSqlQuery')->willReturnCallback(function () {
            return $this->lastQuery = new SqlQuery();
        });

        $this->setProperty($entity, 'queryFactory', $queryFactory);
        $this->setProperty($entity, 'db', new CallbackQueueDatabaseStub($rows, $count));

        return $entity;
    }

    private function setProperty($entity, string $name, $value): void
    {
        $property = new \ReflectionProperty(\Okay\Core\Entity\Entity::class, $name);
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }
        $property->setValue($entity, $value);
    }
}

/**
 * Не нащадок Database навмисне: її деструктор відʼєднується від PDO, тож навіть
 * заглушка вимагає живого зʼєднання.
 */
class CallbackQueueDatabaseStub
{
    private array $rows;
    private string $count;

    public function __construct(array $rows, string $count)
    {
        $this->rows = $rows;
        $this->count = $count;
    }

    public function query($query, $debug = false)
    {
        return true;
    }

    public function results($field = null, $mapped = null)
    {
        return $this->rows;
    }

    public function result($field = null)
    {
        return $this->count;
    }
}
