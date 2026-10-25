<?php

namespace Okay\Modules\Sviat\Ringostat\Backend\Controllers;

use Okay\Admin\Controllers\IndexAdmin;
use Okay\Core\Response;
use Okay\Modules\Sviat\Ringostat\Helpers\RingostatApiClient;
use Okay\Modules\Sviat\Ringostat\Helpers\RingostatSettingsHelper;

/**
 * Налаштування інтеграції Ringostat (API, перемикачі синхронізації).
 */
class RingostatAdmin extends IndexAdmin
{
    public function fetch(RingostatApiClient $apiClient, RingostatSettingsHelper $settingsHelper): Response
    {
        if ($this->request->method('post')) {
            $settingsHelper->setAll([
                'api_key' => trim($this->request->post('sviat__ringostat__auth_key', 'string', '')),
                'project_id' => trim($this->request->post('sviat__ringostat__project_id', 'string', '')),
                'sync_contacts_enabled' => $this->request->post('sviat__ringostat__sync_contacts_enabled', 'integer', 0),
                'show_order_info_in_phone' => $this->request->post('sviat__ringostat__show_order_info_in_phone', 'integer', 0),
                'contact_name_format' => trim($this->request->post('sviat__ringostat__contact_name_format', 'string', '')),
            ]);
            $this->settings->set('sviat__ringostat__sync_on_order', $this->request->post('sviat__ringostat__sync_on_order', 'integer', 0));
            $this->postRedirectGet->storeMessageSuccess('saved');
            $this->postRedirectGet->redirect();
        }

        $this->design->assign('root_url', $this->request->getRootUrl());
        $this->design->assign('auth_key', $settingsHelper->get('api_key'));
        $this->design->assign('project_id', $settingsHelper->get('project_id'));
        $this->design->assign('sync_on_order', (int) $this->settings->get('sviat__ringostat__sync_on_order') === 1);
        $this->design->assign('sync_contacts_enabled', (int) $this->settings->get('sviat__ringostat__sync_contacts_enabled') === 1);
        $this->design->assign('show_order_info_in_phone', $settingsHelper->getShowOrderInfoInPhone());
        $this->design->assign('contact_name_format', $settingsHelper->getContactNameFormat());

        return $this->response->setContent($this->design->fetch('ringostat_admin.tpl'));
    }

    /**
     * AJAX: перевірка з'єднання з API.
     */
    public function testConnection(RingostatApiClient $apiClient): Response
    {
        $authKey = trim($this->request->post('auth_key', 'string', '') ?: $this->request->post('sviat__ringostat__auth_key', 'string', ''));
        $projectId = trim($this->request->post('project_id', 'string', '') ?: $this->request->post('sviat__ringostat__project_id', 'string', ''));
        $apiClient->setCredentials($authKey ?: null, $projectId ?: null);
        $result = $apiClient->testConnection();

        $data = [
            'success' => $result['success'],
            'error' => $result['error'] ?? null,
        ];
        return $this->response->setContent(json_encode($data), RESPONSE_JSON);
    }
}
