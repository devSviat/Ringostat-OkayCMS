<?php

namespace Okay\Modules\Sviat\Ringostat\Init;

use Okay\Admin\Helpers\BackendOrdersHelper;
use Okay\Core\Database;
use Okay\Core\Modules\AbstractInit;
use Okay\Core\Modules\EntityField;
use Okay\Core\QueryFactory;
use Okay\Core\Scheduler\Schedule;
use Okay\Core\ServiceLocator;
use Okay\Admin\Helpers\BackendMainHelper;
use Okay\Helpers\OrdersHelper;
use Okay\Helpers\UserHelper;
use Okay\Modules\Sviat\Ringostat\Entities\RingostatCallsEntity;
use Okay\Modules\Sviat\Ringostat\Entities\RingostatCallbackQueueEntity;
use Okay\Modules\Sviat\Ringostat\Entities\RingostatContactsSyncEntity;
use Okay\Modules\Sviat\Ringostat\Extenders\BackendExtender;
use Okay\Modules\Sviat\Ringostat\Extenders\FrontendExtender;
use Okay\Modules\Sviat\Ringostat\Helpers\RingostatCronHelper;

class Init extends AbstractInit
{
    public function install()
    {
        $this->setBackendMainController('RingostatAdmin');

        $this->migrateEntityTable(RingostatCallsEntity::class, [
            (new EntityField('id'))->setIndexPrimaryKey()->setTypeInt(11, false)->setAutoIncrement(),
            (new EntityField('ringostat_call_id'))->setTypeVarchar(64, false)->setIndexUnique(),
            (new EntityField('direction'))->setTypeEnum(['in', 'out'], true),
            (new EntityField('status'))->setTypeVarchar(64, true),
            (new EntityField('caller'))->setTypeVarchar(128, true)->setIndex(),
            (new EntityField('callee'))->setTypeVarchar(128, true),
            (new EntityField('duration'))->setTypeInt(11, true),
            (new EntityField('waittime'))->setTypeInt(11, true),
            (new EntityField('billsec'))->setTypeInt(11, true),
            (new EntityField('department'))->setTypeVarchar(255, true),
            (new EntityField('call_card'))->setTypeVarchar(512, true),
            (new EntityField('record_url'))->setTypeVarchar(512, true),
            (new EntityField('manager_id'))->setTypeInt(11, true)->setIndex(),
            (new EntityField('employee_fio'))->setTypeVarchar(255, true),
            (new EntityField('utm_source'))->setTypeVarchar(255, true),
            (new EntityField('utm_medium'))->setTypeVarchar(255, true),
            (new EntityField('utm_campaign'))->setTypeVarchar(255, true),
            (new EntityField('started_at'))->setTypeDatetime(true)->setIndex(),
            (new EntityField('created_at'))->setTypeDatetime(true),
        ]);

        $this->migrateEntityTable(RingostatContactsSyncEntity::class, [
            (new EntityField('id'))->setIndexPrimaryKey()->setTypeInt(11, false)->setAutoIncrement(),
            (new EntityField('user_id'))->setTypeInt(11, true)->setIndex(),
            (new EntityField('phone'))->setTypeVarchar(32, true)->setIndex(),
            (new EntityField('name'))->setTypeVarchar(255, true),
            (new EntityField('last_order_id'))->setTypeInt(11, true),
            (new EntityField('last_order_sum'))->setTypeDecimal(10, true),
            (new EntityField('synced_at'))->setTypeDatetime(true),
            (new EntityField('sync_status'))->setTypeEnum(['success', 'error'], true),
            (new EntityField('ringostat_contact_id'))->setTypeVarchar(64, true),
        ]);

        $this->migrateEntityTable(RingostatCallbackQueueEntity::class, self::callbackQueueFields());
    }

    /**
     * Черга передзвону стала похідним списком із журналу дзвінків, а таблиця —
     * лише відмітками оператора. Апдейт має зробити три речі:
     *  - створити таблицю там, де install() з нею ніколи не виконувався
     *    (її додали в install() без підняття версії, тож на частині інсталяцій її немає);
     *  - додати processed_until;
     *  - перенести наявні відмітки, інакше всі раніше оброблені номери спливуть разом.
     */
    public function update_1_0_3()
    {
        if (!$this->tableExists(RingostatCallbackQueueEntity::getTable())) {
            $this->migrateEntityTable(RingostatCallbackQueueEntity::class, self::callbackQueueFields());
        }

        $this->migrateEntityField(
            RingostatCallbackQueueEntity::class,
            (new EntityField('processed_until'))->setTypeDatetime(true)->setIndex()
        );

        $this->backfillProcessedUntil();
        $this->addCallsQueueIndexes();
    }

    /** @return EntityField[] */
    private static function callbackQueueFields(): array
    {
        return [
            (new EntityField('id'))->setIndexPrimaryKey()->setTypeInt(11, false)->setAutoIncrement(),
            (new EntityField('phone'))->setTypeVarchar(32, false)->setIndexUnique(),
            (new EntityField('missed_count'))->setTypeInt(11, false)->setDefault(1),
            (new EntityField('last_missed_at'))->setTypeDatetime(false)->setIndex(),
            (new EntityField('processed'))->setTypeTinyInt(1, false)->setDefault(0)->setIndex(),
            (new EntityField('processed_until'))->setTypeDatetime(true)->setIndex(),
        ];
    }

    /**
     * Для вже позначених оброблених — відмітка «відпрацьовано до останнього
     * пропущеного, який зараз є в журналі». Старий last_missed_at для цього не
     * годиться: інкрементальна логіка писала його від новішого до старішого.
     *
     * Це свідомо гасить номери, які оператор колись позначив і які потім знову
     * дзвонили: інакше після оновлення в список разом висипалось би все, що
     * накопичилось за час, поки відмітка «оброблено» не знімалась.
     *
     * BUSY тут навмисно НЕ враховується, хоч у самому списку він тепер є:
     * під старими правилами оператор таких дзвінків не бачив, тож і «відпрацював»
     * він лише до останнього NO ANSWER/VOICEMAIL. Пізніший BUSY має підняти номер.
     */
    private function backfillProcessedUntil(): void
    {
        $queue = RingostatCallbackQueueEntity::getTable();
        $calls = RingostatCallsEntity::getTable();

        $this->sqlQuery(
            "UPDATE `{$queue}` q
             SET q.processed_until = (
                 SELECT MAX(c.started_at) FROM `{$calls}` c
                 WHERE c.direction = 'in' AND c.status IN ('NO ANSWER', 'VOICEMAIL') AND c.caller = q.phone
             )
             WHERE q.processed = 1 AND q.processed_until IS NULL"
        )->execute();
    }

    /** Похідний список групує журнал по номеру — без цих індексів це full scan. */
    private function addCallsQueueIndexes(): void
    {
        $calls = RingostatCallsEntity::getTable();

        $existing = [];
        $this->sqlQuery("SHOW INDEX FROM `{$calls}`")->execute();
        foreach (ServiceLocator::getInstance()->getService(Database::class)->results() as $row) {
            $existing[$row->Key_name] = true;
        }

        if (!isset($existing['status_direction_started_at'])) {
            $this->sqlQuery("ALTER TABLE `{$calls}` ADD INDEX `status_direction_started_at` (`status`, `direction`, `started_at`)")->execute();
        }
        if (!isset($existing['callee'])) {
            $this->sqlQuery("ALTER TABLE `{$calls}` ADD INDEX `callee` (`callee`)")->execute();
        }
    }

    private function tableExists(string $table): bool
    {
        return ServiceLocator::getInstance()
            ->getService(Database::class)
            ->query($this->sqlQuery("SELECT 1 FROM `{$table}` LIMIT 1")) === true;
    }

    private function sqlQuery(string $statement)
    {
        return ServiceLocator::getInstance()
            ->getService(QueryFactory::class)
            ->newSqlQuery()
            ->setStatement($statement);
    }

    public function init()
    {
        $this->registerBackendController('RingostatAdmin');
        $this->addBackendControllerPermission('RingostatAdmin', 'sviat__ringostat_settings');

        $this->registerBackendController('RingostatCallsAdmin');
        $this->addBackendControllerPermission('RingostatCallsAdmin', 'sviat__ringostat_calls');

        $this->registerBackendController('RingostatCallbackQueueAdmin');
        $this->addBackendControllerPermission('RingostatCallbackQueueAdmin', 'sviat__ringostat_callback_queue');

        $this->registerQueueExtension(
            [OrdersHelper::class, 'finalCreateOrderProcedure'],
            [FrontendExtender::class, 'syncContactAfterOrder']
        );
        $this->registerQueueExtension(
            [UserHelper::class, 'register'],
            [FrontendExtender::class, 'syncContactAfterUserRegister']
        );

        $this->registerChainExtension(
            ['class' => BackendMainHelper::class, 'method' => 'evensCounters'],
            ['class' => BackendExtender::class, 'method' => 'setCallbackQueueCounter']
        );

        $this->registerQueueExtension(
            [BackendOrdersHelper::class, 'findOrder'],
            [BackendExtender::class, 'findOrder']
        );

        $this->registerSchedule(
            (new Schedule([RingostatCronHelper::class, 'syncCalls']))
                ->name('Ringostat: sync calls from API')
                ->time('*/2 * * * *')
                ->overlap(false)
                ->timeout(300)
        );

        $this->extendBackendMenu(
            'sviat__left_ringostat',
            [
                'sviat__left_ringostat_settings' => ['RingostatAdmin'],
                'sviat__left_ringostat_calls' => ['RingostatCallsAdmin'],
                'sviat__left_ringostat_callback_queue' => ['RingostatCallbackQueueAdmin'],
            ],
            '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-phone-call"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2" /><path d="M15 7a2 2 0 0 1 2 2" /><path d="M15 3a6 6 0 0 1 6 6" /></svg>'
        );
    }
}
