<?php

namespace Okay\Modules\Sviat\Ringostat\Helpers;

use Psr\Log\LoggerInterface;

/**
 * Клієнт API Ringostat.
 * Документація: https://help.ringostat.com/uk/collections/106988-api
 */
class RingostatApiClient
{
    private const API_BASE = 'https://api.ringostat.net';

    /** Максимум дзвінків за один запит (обмеження Ringostat API). @see https://help.ringostat.com/uk/articles/6312678 */
    public const MAX_CALLS_PER_REQUEST = 6500;

    /** @var string|null */
    private $authKey;

    /** @var string|null */
    private $projectId;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        \Okay\Core\Settings $settings,
        LoggerInterface $logger
    ) {
        $this->authKey = $settings->get('sviat__ringostat__auth_key');
        $this->projectId = $settings->get('sviat__ringostat__project_id');
        $this->logger = $logger;
    }

    public function setCredentials(?string $authKey, ?string $projectId): void
    {
        $this->authKey = $authKey;
        $this->projectId = $projectId;
    }

    public function isConfigured(): bool
    {
        return !empty($this->authKey) && !empty($this->projectId);
    }

    /** Синхронізація контактів (minicrm/contacts/sync). */
    public function syncContacts(array $contacts): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Ringostat: не налаштовано auth_key або project_id', 'response' => null];
        }

        $url = self::API_BASE . '/minicrm/contacts/sync';
        $body = json_encode(['contacts' => $contacts]);
        $headers = [
            'Content-Type: application/json',
            'auth-key: ' . $this->authKey,
            'x-project-id: ' . $this->projectId,
        ];

        $result = $this->request('POST', $url, $headers, $body);

        if ($result['http_code'] >= 200 && $result['http_code'] < 300) {
            return ['success' => true, 'error' => null, 'response' => $result['body']];
        }

        $errorMsg = $result['error'] ?? (is_array($result['body']) && isset($result['body']['message']) ? $result['body']['message'] : 'HTTP ' . $result['http_code']);
        $this->logger->warning('Ringostat syncContacts failed', [
            'http_code' => $result['http_code'],
            'error' => $errorMsg,
            'body' => $result['body'],
        ]);
        return ['success' => false, 'error' => $errorMsg, 'response' => $result['body']];
    }

    /** Експорт журналу дзвінків (GET calls/list). Параметри: from, to, fields, order, merge. */
    public function getCallsList(array $params = []): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Ringostat: не налаштовано auth_key або project_id', 'calls' => [], 'response' => null];
        }

        $defaults = [
            'projectId' => $this->projectId,
            'export_type' => 'json',
            'from' => date('Y-m-d H:i:s', strtotime('-24 hours')),
            'to' => date('Y-m-d H:i:s'),
            'fields' => 'uniqueid,calldate,caller,caller_number,dst,disposition,duration,billsec,call_type,recording,recording_wav,employee_number,employee_fio,utm_source,utm_medium,utm_campaign',
            'merge' => 0,
            'order' => 'calldate desc',
        ];
        $queryParams = array_merge($defaults, $params);
        $url = self::API_BASE . '/calls/list?' . http_build_query($queryParams);
        $headers = [
            'Auth-key: ' . $this->authKey,
            'accept: application/json',
        ];

        $result = $this->request('GET', $url, $headers);

        if ($result['http_code'] === 401) {
            return ['success' => false, 'error' => 'Невірний Auth-key', 'calls' => [], 'response' => $result['body']];
        }
        if ($result['http_code'] === 403) {
            return ['success' => false, 'error' => 'Доступ заборонено', 'calls' => [], 'response' => $result['body']];
        }
        if ($result['http_code'] >= 200 && $result['http_code'] < 300) {
            $body = $result['body'];
            $calls = is_array($body) ? $body : (is_array($body['data'] ?? null) ? $body['data'] : []);
            return ['success' => true, 'error' => null, 'calls' => $calls, 'response' => $body];
        }

        return [
            'success' => false,
            'error' => $result['error'] ?? 'HTTP ' . $result['http_code'],
            'calls' => [],
            'response' => $result['body'],
        ];
    }

    /** Перевірка з'єднання (getCallsList за останню годину). */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Не вказано Auth-key або Project ID'];
        }

        $to = date('Y-m-d H:i:s');
        $from = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $r = $this->getCallsList(['from' => $from, 'to' => $to, 'fields' => 'calldate,caller,dst']);

        if ($r['success']) {
            return ['success' => true, 'error' => null];
        }
        return ['success' => false, 'error' => $r['error']];
    }

    /** Callback outward_call: extension → destination. @see https://ringostat.readme.io/reference/post_callback-outward-call */
    public function callbackOutwardCall(string $extension, string $destination): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Ringostat: не налаштовано auth_key або project_id', 'response' => null];
        }

        $url = self::API_BASE . '/callback/outward_call';
        $body = http_build_query([
            'extension' => $extension,
            'destination' => $destination,
        ]);
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'Auth-key: ' . $this->authKey,
        ];

        $result = $this->request('POST', $url, $headers, $body);

        if ($result['http_code'] >= 200 && $result['http_code'] < 300) {
            return ['success' => true, 'error' => null, 'response' => $result['body']];
        }

        $errorMsg = $result['error'] ?? (is_array($result['body']) && isset($result['body']['message']) ? $result['body']['message'] : 'HTTP ' . $result['http_code']);
        $this->logger->warning('Ringostat callbackOutwardCall failed', [
            'http_code' => $result['http_code'],
            'error' => $errorMsg,
        ]);
        return ['success' => false, 'error' => $errorMsg, 'response' => $result['body']];
    }

    /** Розширений callback (JSON-RPC a/v2). Параметри: caller, callee, direction, manager_dst, utm*. */
    public function callbackExternal(array $params): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Ringostat: не налаштовано auth_key або project_id', 'response' => null];
        }

        $rpcParams = [
            'projectId' => (string) ($params['projectId'] ?? $this->projectId),
            'callee_type' => $params['callee_type'] ?? 'default',
            'caller_type' => $params['caller_type'] ?? 'default',
            'caller' => $params['caller'] ?? '',
            'callee' => $params['callee'] ?? '',
            'direction' => $params['direction'] ?? 'out',
            'manager_dst' => (int) ($params['manager_dst'] ?? 1),
        ];
        $allowed = ['clientIp', 'utmSource', 'utmMedium', 'utmCampaign', 'utmTerm', 'utmContent', 'clientId', 'clientUserAgent'];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $params) && $params[$key] !== '' && $params[$key] !== null) {
                $rpcParams[$key] = $params[$key];
            }
        }

        $body = json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'Api\\V2\\Callback.external',
            'params' => $rpcParams,
        ]);

        $url = self::API_BASE . '/a/v2';
        $headers = [
            'Content-Type: application/json',
            'Auth-key: ' . $this->authKey,
        ];

        $result = $this->request('POST', $url, $headers, $body);

        if ($result['http_code'] >= 200 && $result['http_code'] < 300) {
            $hasError = isset($result['body']['error']);
            return [
                'success' => !$hasError,
                'error' => $hasError ? ($result['body']['error']['message'] ?? json_encode($result['body']['error'])) : null,
                'response' => $result['body'],
            ];
        }

        $errorMsg = $result['error'] ?? (is_array($result['body']) && isset($result['body']['error']['message']) ? $result['body']['error']['message'] : 'HTTP ' . $result['http_code']);
        $this->logger->warning('Ringostat callbackExternal failed', [
            'http_code' => $result['http_code'],
            'error' => $errorMsg,
        ]);
        return ['success' => false, 'error' => $errorMsg, 'response' => $result['body']];
    }

    /** SIP-акаунти Online. @return array{success: bool, error: ?string, logins: string[]} */
    public function getSipOnline(): array
    {
        if (!$this->authKey) {
            return ['success' => false, 'error' => 'Ringostat: не вказано auth_key', 'logins' => []];
        }

        $url = self::API_BASE . '/sipstatus/online';
        $headers = ['Auth-key: ' . $this->authKey];
        $result = $this->request('GET', $url, $headers);

        if ($result['http_code'] === 401) {
            return ['success' => false, 'error' => 'Невірний Auth-key', 'logins' => []];
        }
        if ($result['http_code'] === 403) {
            return ['success' => false, 'error' => 'Доступ заборонено', 'logins' => []];
        }
        if ($result['http_code'] >= 200 && $result['http_code'] < 300) {
            $logins = is_array($result['body']) ? $result['body'] : [];
            return ['success' => true, 'error' => null, 'logins' => $logins];
        }
        return [
            'success' => false,
            'error' => $result['error'] ?? 'HTTP ' . $result['http_code'],
            'logins' => [],
        ];
    }

    /** SIP-акаунти в розмові. @return array{success: bool, error: ?string, logins: string[]} */
    public function getSipSpeaking(): array
    {
        if (!$this->authKey) {
            return ['success' => false, 'error' => 'Ringostat: не вказано auth_key', 'logins' => []];
        }

        $url = self::API_BASE . '/sipstatus/speaking';
        $headers = ['Auth-key: ' . $this->authKey];
        $result = $this->request('GET', $url, $headers);

        if ($result['http_code'] === 401) {
            return ['success' => false, 'error' => 'Невірний Auth-key', 'logins' => []];
        }
        if ($result['http_code'] === 403) {
            return ['success' => false, 'error' => 'Доступ заборонено', 'logins' => []];
        }
        if ($result['http_code'] >= 200 && $result['http_code'] < 300) {
            $logins = is_array($result['body']) ? $result['body'] : [];
            return ['success' => true, 'error' => null, 'logins' => $logins];
        }
        return [
            'success' => false,
            'error' => $result['error'] ?? 'HTTP ' . $result['http_code'],
            'logins' => [],
        ];
    }

    /** Відправка організацій (minicrm/organizations/sync). */
    public function syncOrganizations(array $organizations): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Ringostat: не налаштовано auth_key або project_id', 'response' => null];
        }

        $url = self::API_BASE . '/minicrm/organizations/sync';
        $body = json_encode(['organizations' => $organizations]);
        $headers = [
            'Content-Type: application/json',
            'auth-key: ' . $this->authKey,
            'x-project-id: ' . $this->projectId,
        ];

        $result = $this->request('POST', $url, $headers, $body);

        if ($result['http_code'] >= 200 && $result['http_code'] < 300) {
            return ['success' => true, 'error' => null, 'response' => $result['body']];
        }

        $errorMsg = $result['error'] ?? (is_array($result['body']) && isset($result['body']['message']) ? $result['body']['message'] : 'HTTP ' . $result['http_code']);
        $this->logger->warning('Ringostat syncOrganizations failed', [
            'http_code' => $result['http_code'],
            'error' => $errorMsg,
        ]);
        return ['success' => false, 'error' => $errorMsg, 'response' => $result['body']];
    }

    private function request(string $method, string $url, array $headers, ?string $body = null): array
    {
        if (function_exists('curl_init')) {
            return $this->requestCurl($method, $url, $headers, $body);
        }
        return $this->requestFileGetContents($method, $url, $headers, $body);
    }

    private function requestCurl(string $method, string $url, array $headers, ?string $body): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        if ($body !== null && $body !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        $decoded = null;
        if ($response !== false && $response !== '') {
            $decoded = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $decoded = ['raw' => $response];
            }
        }
        return [
            'http_code' => $httpCode,
            'body' => $decoded,
            'error' => $response === false ? $err : null,
        ];
    }

    private function requestFileGetContents(string $method, string $url, array $headers, ?string $body): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => $body ?? '',
                'ignore_errors' => true,
                'timeout' => 15,
            ],
            'ssl' => ['verify_peer' => true],
        ]);
        $response = @file_get_contents($url, false, $context);
        $httpCode = 0;
        if (isset($http_response_header[0]) && preg_match('/\d{3}/', $http_response_header[0], $m)) {
            $httpCode = (int) $m[0];
        }
        $decoded = null;
        if ($response !== false && $response !== '') {
            $decoded = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $decoded = ['raw' => $response];
            }
        }
        return [
            'http_code' => $httpCode,
            'body' => $decoded,
            'error' => $response === false ? (error_get_last()['message'] ?? 'Unknown error') : null,
        ];
    }
}
