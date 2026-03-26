<?php

namespace Okay\Modules\Sviat\Ringostat\Backend\Controllers;

use Okay\Admin\Controllers\IndexAdmin;
use Okay\Core\EntityFactory;
use Okay\Core\Response;
use Okay\Modules\Sviat\Ringostat\Entities\RingostatCallsEntity;
use Okay\Modules\Sviat\Ringostat\Entities\RingostatCallbackQueueEntity;
use Okay\Modules\Sviat\Ringostat\Helpers\RingostatPhoneFormatHelper;

/**
 * Черга передзвону: номери з пропущеними вхідними (in + NO ANSWER/VOICEMAIL).
 */
class RingostatCallbackQueueAdmin extends IndexAdmin
{
    private const CONTROLLER_NAME = 'Sviat.Ringostat.RingostatCallbackQueueAdmin';

    public function fetch(EntityFactory $entityFactory): Response
    {
        if ($this->request->method('post')) {
            $this->handleBulkActions($entityFactory);
            $this->postRedirectGet->redirect($this->request->getRootUrl() . '/backend/index.php?controller=' . self::CONTROLLER_NAME);
            return $this->response;
        }

        /** @var RingostatCallbackQueueEntity $queueEntity */
        $queueEntity = $entityFactory->get(RingostatCallbackQueueEntity::class);

        $items = $queueEntity->find(['processed' => 0]);
        $rootUrl = $this->request->getRootUrl();
        $callsBaseUrl = $rootUrl . '/backend/index.php?controller=Sviat.Ringostat.RingostatCallsAdmin';

        $voicemailByPhone = $this->getVoicemailRecordUrlByPhones($entityFactory, $items);

        foreach ($items as $item) {
            $item->display_phone = $item->phone !== '' && $item->phone !== null
                ? RingostatPhoneFormatHelper::formatDisplay($item->phone)
                : '';
        }

        $this->design->assign('queue_items', $items);
        $this->design->assign('voicemail_by_phone', $voicemailByPhone);
        $this->design->assign('root_url', $rootUrl);
        $this->design->assign('ringostat_calls_base_url', $callsBaseUrl);

        return $this->response->setContent($this->design->fetch('ringostat_callback_queue.tpl'));
    }

    /** Мапа [phone => record_url] для номерів з VOICEMAIL-записом. */
    private function getVoicemailRecordUrlByPhones(EntityFactory $entityFactory, array $queueItems): array
    {
        $phones = [];
        foreach ($queueItems as $item) {
            if (!empty($item->phone)) {
                $phones[$item->phone] = true;
            }
        }
        if (empty($phones)) {
            return [];
        }

        /** @var RingostatCallsEntity $callsEntity */
        $callsEntity = $entityFactory->get(RingostatCallsEntity::class);
        $voicemailCalls = $callsEntity->find(['status' => 'VOICEMAIL', 'limit' => 500]);
        $result = [];
        foreach ($voicemailCalls as $c) {
            if (empty($c->record_url)) {
                continue;
            }
            $clientPhone = ($c->direction ?? '') === 'in' ? ($c->caller ?? '') : ($c->callee ?? '');
            if ($clientPhone !== '' && isset($phones[$clientPhone])) {
                $result[$clientPhone] = $c->record_url;
            }
        }
        return $result;
    }

    /** Масове позначення як оброблені (check + action). */
    private function handleBulkActions(EntityFactory $entityFactory): void
    {
        /** @var RingostatCallbackQueueEntity $queueEntity */
        $queueEntity = $entityFactory->get(RingostatCallbackQueueEntity::class);

        $ids = [];
        $single = $this->request->post('single_action');
        if (is_array($single)) {
            foreach ($single as $id => $action) {
                if ($action === 'mark_processed') {
                    $ids[] = (int) $id;
                }
            }
        }
        $check = (array) $this->request->post('check');
        if (!empty($check) && $this->request->post('action') === 'mark_processed') {
            foreach ($check as $id) {
                $ids[] = (int) $id;
            }
        }
        $ids = array_unique(array_filter($ids));
        foreach ($ids as $id) {
            $item = $queueEntity->get($id);
            if ($item) {
                $queueEntity->update($id, (object) ['processed' => 1]);
            }
        }
    }

    /** Позначити один запис як оброблений (POST). */
    public function markProcessed(EntityFactory $entityFactory): Response
    {
        $id = (int) $this->request->post('id', 'integer', 0);
        if ($id <= 0) {
            $this->postRedirectGet->redirect($this->request->getRootUrl() . '/backend/index.php?controller=' . self::CONTROLLER_NAME);
            return $this->response;
        }

        /** @var RingostatCallbackQueueEntity $queueEntity */
        $queueEntity = $entityFactory->get(RingostatCallbackQueueEntity::class);
        $item = $queueEntity->get($id);
        if ($item) {
            $queueEntity->update($id, (object) ['processed' => 1]);
        }

        $this->postRedirectGet->redirect($this->request->getRootUrl() . '/backend/index.php?controller=' . self::CONTROLLER_NAME);
        return $this->response;
    }
}
