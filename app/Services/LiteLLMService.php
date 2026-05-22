<?php

namespace App\Services;

use App\Exceptions\LiteLLMDatabaseException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class LiteLLMService
{
    protected string $apiUrl;
    protected string $apiKey;
    protected int $cacheTtl;
    protected int $timeout;
    protected int $retryTimes;
    protected int $retryDelay;

    public function __construct()
    {
        $config = config('litellm');
        $this->apiUrl = rtrim($config['api_url'], '/');
        $this->apiKey = $config['api_key'];
        $this->cacheTtl = $config['cache_ttl'];
        $this->timeout = $config['timeout'];
        $this->retryTimes = $config['retry_times'];
        $this->retryDelay = $config['retry_delay'];
    }

    protected function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout($this->timeout)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]);
    }

    protected function withRetry(callable $callback, string $cacheKey = null)
    {
        $lastException = null;
        $attempts = 0;

        while ($attempts < $this->retryTimes) {
            try {
                return $callback();
            } catch (\Exception $e) {
                $lastException = $e;
                $attempts++;
                if ($attempts < $this->retryTimes) {
                    usleep($this->retryDelay * 1000);
                }
            }
        }

        throw $lastException;
    }

    protected function getCacheKey(string $endpoint, array $params = []): string
    {
        return 'litellm:' . $endpoint . ':' . md5(json_encode($params));
    }

    protected function checkDatabaseError(\Illuminate\Http\Client\Response $response): void
    {
        $body = $response->body();
        if (str_contains($body, 'No connected db') || str_contains($body, 'no connected db')) {
            throw LiteLLMDatabaseException::fromResponse($body);
        }
    }

    public function listKeys(): array
    {
        $cacheKey = $this->getCacheKey('keys_list');

        return Cache::remember($cacheKey, $this->cacheTtl, function () {
            return $this->withRetry(function () {
                $response = $this->http()->get($this->apiUrl . '/key/list');
                $this->checkDatabaseError($response);
                $this->validateResponse($response);
                return $this->normalizeKeyListPayload($response->json());
            });
        });
    }

    public function getKeyInfo(string $key): array
    {
        $cacheKey = $this->getCacheKey('key_info', ['key' => $key]);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($key) {
            return $this->withRetry(function () use ($key) {
                $response = $this->http()->get($this->apiUrl . '/key/info', ['key' => $key]);
                $this->checkDatabaseError($response);
                $this->validateResponse($response);
                return $this->normalizeKeyPayload($response->json());
            });
        });
    }

    public function generateKey(array $data): string
    {
        Cache::forget($this->getCacheKey('keys_list'));

        return $this->withRetry(function () use ($data) {
            $response = $this->http()->post($this->apiUrl . '/key/generate', $data);
            $this->checkDatabaseError($response);
            $this->validateResponse($response);
            return $response->json('key');
        });
    }

    public function deleteKey(string $key): bool
    {
        Cache::forget($this->getCacheKey('keys_list'));
        Cache::forget($this->getCacheKey('key_info', ['key' => $key]));

        return $this->withRetry(function () use ($key) {
            $response = $this->http()->post($this->apiUrl . '/key/delete', ['key' => $key]);
            $this->checkDatabaseError($response);
            $this->validateResponse($response);
            return $response->successful();
        });
    }

    public function blockKey(string $key): bool
    {
        Cache::forget($this->getCacheKey('keys_list'));
        Cache::forget($this->getCacheKey('key_info', ['key' => $key]));

        return $this->withRetry(function () use ($key) {
            $response = $this->http()->post($this->apiUrl . '/key/block', ['key' => $key]);
            $this->checkDatabaseError($response);
            $this->validateResponse($response);
            return $response->successful();
        });
    }

    public function unblockKey(string $key): bool
    {
        Cache::forget($this->getCacheKey('keys_list'));
        Cache::forget($this->getCacheKey('key_info', ['key' => $key]));

        return $this->withRetry(function () use ($key) {
            $response = $this->http()->post($this->apiUrl . '/key/unblock', ['key' => $key]);
            $this->checkDatabaseError($response);
            $this->validateResponse($response);
            return $response->successful();
        });
    }

    public function updateKey(string $key, array $data): array
    {
        Cache::forget($this->getCacheKey('keys_list'));
        Cache::forget($this->getCacheKey('key_info', ['key' => $key]));

        return $this->withRetry(function () use ($key, $data) {
            $data['key'] = $key;
            $response = $this->http()->post($this->apiUrl . '/key/update', $data);
            $this->checkDatabaseError($response);
            $this->validateResponse($response);
            return $response->json();
        });
    }

    public function listUsers(): array
    {
        $cacheKey = $this->getCacheKey('users_list');

        return Cache::remember($cacheKey, $this->cacheTtl, function () {
            return $this->withRetry(function () {
                $response = $this->http()->get($this->apiUrl . '/user/list');
                $this->checkDatabaseError($response);
                $this->validateResponse($response);
                return $response->json();
            });
        });
    }

    public function getUserInfo(string $userId): array
    {
        $cacheKey = $this->getCacheKey('user_info', ['user_id' => $userId]);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($userId) {
            return $this->withRetry(function () use ($userId) {
                $response = $this->http()->get($this->apiUrl . '/user/info', ['user_id' => $userId]);
                $this->checkDatabaseError($response);
                $this->validateResponse($response);
                return $response->json();
            });
        });
    }

    public function getUserDailyActivity(string $startDate, string $endDate): array
    {
        $cacheKey = $this->getCacheKey('user_activity', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($startDate, $endDate) {
            return $this->withRetry(function () use ($startDate, $endDate) {
                $response = $this->http()->get($this->apiUrl . '/user/daily/activity', [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ]);
                $this->checkDatabaseError($response);
                $this->validateResponse($response);
                return $response->json();
            });
        });
    }

    public function getGlobalSpendReport(string $startDate, string $endDate): array
    {
        $cacheKey = $this->getCacheKey('global_spend', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($startDate, $endDate) {
            return $this->withRetry(function () use ($startDate, $endDate) {
                $response = $this->http()->get($this->apiUrl . '/global/spend/report', [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ]);
                $this->checkDatabaseError($response);
                $this->validateResponse($response);
                return $response->json();
            });
        });
    }

    public function getDailySpendReport(string $startDate, string $endDate): array
    {
        try {
            return $this->getGlobalSpendReport($startDate, $endDate);
        } catch (\Exception $e) {
            if (!$this->isEnterpriseSpendReportError($e)) {
                throw $e;
            }
        }

        $logs = $this->getSpendLogs($startDate, $endDate, true);

        return $this->normalizeDailySpendPayload($logs);
    }

    public function getSpendLogs(string $startDate, string $endDate, bool $summarize = false): array
    {
        $cacheKey = $this->getCacheKey('spend_logs', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'summarize' => $summarize,
        ]);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($startDate, $endDate, $summarize) {
            return $this->withRetry(function () use ($startDate, $endDate, $summarize) {
                $response = $this->http()->get($this->apiUrl . '/spend/logs', [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'summarize' => $summarize ? 'true' : 'false',
                ]);
                $this->checkDatabaseError($response);
                $this->validateResponse($response);
                return $response->json();
            });
        });
    }

    protected function isEnterpriseSpendReportError(\Exception $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, '/spend/report endpoint')
            && str_contains($message, 'LiteLLM Enterprise user');
    }

    protected function normalizeDailySpendPayload(array $payload): array
    {
        if (isset($payload['spend']) && is_array($payload['spend'])) {
            return ['spend' => $payload['spend']];
        }

        $candidates = [
            $payload['daily_spend'] ?? null,
            $payload['dailySpend'] ?? null,
            $payload['logs'] ?? null,
            $payload['data'] ?? null,
            $payload,
        ];

        foreach ($candidates as $candidate) {
            if (is_array($candidate) && array_is_list($candidate)) {
                return [
                    'spend' => $this->aggregateSpendByDate($candidate),
                ];
            }
        }

        return ['spend' => []];
    }

    protected function aggregateSpendByDate(array $items): array
    {
        $dailySpend = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $date = $this->extractSpendDate($item);
            if (!$date) {
                continue;
            }

            $spend = (float) ($item['spend'] ?? $item['total_spend'] ?? $item['cost'] ?? 0);

            if (!isset($dailySpend[$date])) {
                $dailySpend[$date] = [
                    'date' => $date,
                    'spend' => 0.0,
                ];
            }

            $dailySpend[$date]['spend'] += $spend;
        }

        ksort($dailySpend);

        return array_values($dailySpend);
    }

    protected function extractSpendDate(array $item): ?string
    {
        $value = $item['date']
            ?? $item['startTime']
            ?? $item['start_time']
            ?? $item['timestamp']
            ?? $item['created_at']
            ?? null;

        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function normalizeKeyListPayload(array $payload): array
    {
        $items = $payload['keys']
            ?? $payload['data']['keys']
            ?? $payload['data']['tokens']
            ?? $payload['tokens']
            ?? $payload['data']
            ?? $payload;

        if (!is_array($items)) {
            return ['keys' => []];
        }

        $normalized = [];

        foreach ($this->prepareKeyCandidates($items) as $candidate) {
            $normalizedKey = $this->normalizeKeyCandidate($candidate);

            if (empty($normalizedKey['key'])) {
                continue;
            }

            $normalized[$normalizedKey['key']] = $normalizedKey;
        }

        return ['keys' => array_values($normalized)];
    }

    protected function normalizeKeyPayload(array $payload): array
    {
        $info = is_array($payload['info'] ?? null) ? $payload['info'] : [];
        $metadata = is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [];
        $rawModels = $payload['models'] ?? $info['models'] ?? $metadata['models'] ?? [];
        $rawAliases = $payload['aliases'] ?? $info['aliases'] ?? $metadata['aliases'] ?? $payload['key_alias'] ?? $payload['alias'] ?? null;
        $spend = $payload['spend'] ?? $payload['current_spend'] ?? $payload['total_spend'] ?? $info['spend'] ?? $info['current_spend'] ?? 0;
        $maxBudget = $payload['max_budget'] ?? $payload['budget'] ?? $info['max_budget'] ?? $info['budget'] ?? 0;
        $userId = $payload['user_id'] ?? $info['user_id'] ?? $metadata['user_id'] ?? null;
        $blocked = $payload['blocked'] ?? $payload['is_blocked'] ?? $info['blocked'] ?? $info['is_blocked'] ?? false;
        $expires = $payload['expires'] ?? $payload['expires_at'] ?? $payload['expiry'] ?? $info['expires'] ?? $info['expires_at'] ?? null;
        $config = $payload['config'] ?? $info['config'] ?? [];

        return [
            'key' => $payload['key']
                ?? $payload['token']
                ?? $payload['token_id']
                ?? $payload['token_name']
                ?? $payload['token_value']
                ?? $payload['virtual_key']
                ?? $info['key']
                ?? $info['token']
                ?? null,
            'user_id' => $userId,
            'spend' => (float) $spend,
            'max_budget' => (float) $maxBudget,
            'expires' => $expires,
            'models' => $this->normalizeStringList($rawModels),
            'aliases' => $this->normalizeAliases($rawAliases),
            'blocked' => (bool) $blocked,
            'config' => is_array($config) ? $config : [],
            'metadata' => $metadata,
        ];
    }

    protected function prepareKeyCandidates(array $items): array
    {
        if (array_is_list($items)) {
            return $items;
        }

        $candidates = [];

        foreach ($items as $mapKey => $item) {
            if (is_array($item)) {
                if (!isset($item['key']) && !isset($item['token']) && is_string($mapKey)) {
                    $item['key'] = $mapKey;
                }

                $candidates[] = $item;
                continue;
            }

            if (is_string($item)) {
                $candidates[] = ['key' => $item];
                continue;
            }

            if (is_string($mapKey)) {
                $candidates[] = ['key' => $mapKey];
            }
        }

        return $candidates;
    }

    protected function normalizeKeyCandidate(mixed $candidate): array
    {
        if (is_string($candidate)) {
            return $this->hydrateKeyByIdentifier($candidate);
        }

        if (!is_array($candidate)) {
            return [];
        }

        $normalized = $this->normalizeKeyPayload($candidate);

        if (empty($normalized['key'])) {
            return [];
        }

        if ($this->shouldHydrateKeyDetail($normalized)) {
            $detail = $this->getKeyInfo($normalized['key']);
            $normalized = $this->mergeNormalizedKeyData($normalized, $detail);
        }

        return $normalized;
    }

    protected function shouldHydrateKeyDetail(array $key): bool
    {
        return empty($key['user_id'])
            && empty($key['aliases'])
            && empty($key['models'])
            && empty($key['expires'])
            && (float) ($key['spend'] ?? 0) === 0.0
            && (float) ($key['max_budget'] ?? 0) === 0.0
            && empty($key['metadata']);
    }

    protected function hydrateKeyByIdentifier(string $key): array
    {
        try {
            return $this->getKeyInfo($key);
        } catch (\Exception $e) {
            return [
                'key' => $key,
                'user_id' => null,
                'spend' => 0.0,
                'max_budget' => 0.0,
                'expires' => null,
                'models' => [],
                'aliases' => [],
                'blocked' => false,
                'config' => [],
                'metadata' => [],
            ];
        }
    }

    protected function mergeNormalizedKeyData(array $base, array $detail): array
    {
        return [
            'key' => $detail['key'] ?: $base['key'],
            'user_id' => $detail['user_id'] ?: $base['user_id'],
            'spend' => (float) (($detail['spend'] ?? 0) ?: ($base['spend'] ?? 0)),
            'max_budget' => (float) (($detail['max_budget'] ?? 0) ?: ($base['max_budget'] ?? 0)),
            'expires' => $detail['expires'] ?: $base['expires'],
            'models' => !empty($detail['models']) ? $detail['models'] : $base['models'],
            'aliases' => !empty($detail['aliases']) ? $detail['aliases'] : $base['aliases'],
            'blocked' => (bool) ($detail['blocked'] ?? $base['blocked'] ?? false),
            'config' => !empty($detail['config']) ? $detail['config'] : $base['config'],
            'metadata' => !empty($detail['metadata']) ? $detail['metadata'] : $base['metadata'],
        ];
    }

    protected function normalizeAliases(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(fn ($item) => is_scalar($item) ? trim((string) $item) : null, $value)));
        }

        if (is_string($value) && trim($value) !== '') {
            return [trim($value)];
        }

        return [];
    }

    protected function normalizeStringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $values = [];

        foreach ($value as $item) {
            if (is_scalar($item)) {
                $string = trim((string) $item);
                if ($string !== '') {
                    $values[] = $string;
                }
            }
        }

        return array_values(array_unique($values));
    }

    public function listModels(): array
    {
        $cacheKey = $this->getCacheKey('models');

        return Cache::remember($cacheKey, 3600, function () {
            return $this->withRetry(function () {
                $response = $this->http()->get($this->apiUrl . '/models');
                $this->checkDatabaseError($response);
                $this->validateResponse($response);
                return $response->json();
            });
        });
    }

    public function getApiHealth(): bool
    {
        $cacheKey = $this->getCacheKey('health');

        return Cache::remember($cacheKey, $this->cacheTtl, function () {
            try {
                $response = $this->http()->get($this->apiUrl . '/health');
                $this->checkDatabaseError($response);
                return $response->successful();
            } catch (\Exception $e) {
                return false;
            }
        });
    }

    protected function validateResponse(\Illuminate\Http\Client\Response $response): void
    {
        if (!$response->successful()) {
            throw new \Exception('LiteLLM API Error: ' . $response->body(), $response->status());
        }
    }
}
