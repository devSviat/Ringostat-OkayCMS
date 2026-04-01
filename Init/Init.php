<?php

namespace Okay\Modules\Sviat\Ringostat\Init;

use Okay\Admin\Helpers\BackendOrdersHelper;
use Okay\Core\Modules\AbstractInit;
use Okay\Core\Modules\EntityField;
use Okay\Core\Scheduler\Schedule;
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

        $this->migrateEntityTable(RingostatCallbackQueueEntity::class, [
            (new EntityField('id'))->setIndexPrimaryKey()->setTypeInt(11, false)->setAutoIncrement(),
            (new EntityField('phone'))->setTypeVarchar(32, false)->setIndexUnique(),
            (new EntityField('missed_count'))->setTypeInt(11, false)->setDefault(1),
            (new EntityField('last_missed_at'))->setTypeDatetime(false)->setIndex(),
            (new EntityField('processed'))->setTypeTinyInt(1, false)->setDefault(0)->setIndex(),
        ]);
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
