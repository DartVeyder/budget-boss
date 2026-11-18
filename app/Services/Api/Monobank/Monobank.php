<?php

namespace App\Services\Api\Monobank;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Monobank
{
    /**
     * Get client info including all accounts (personal cards, FOP, currency accounts).
     * Cached for 60 seconds to respect Monobank's rate limit.
     */
    public static function getClientInfo(?string $token = null, bool $forceFresh = false): array|bool
    {
        $resolvedToken = $token ?? env("API_KEY_MONOBANK");
        if (!$resolvedToken) {
            return false;
        }

        $cacheKey = 'monobank_client_info_' . md5($resolvedToken);

        if (!$forceFresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = self::request('/personal/client-info', $resolvedToken);
        if ($response && is_array($response) && isset($response['accounts'])) {
            Cache::put($cacheKey, $response, 60);
            return $response;
        }

        return false;
    }

    /**
     * Get statement for a given account ('0' for default personal card, or account ID for FOP/other cards).
     *
     * @param mixed $from Carbon|string|int
     * @param mixed $to Carbon|string|int
     * @param string|null $token
     * @param string $account '0' or specific account ID
     * @return array|bool
     */
    public static function getStatement($from, $to, ?string $token = null, string $account = '0'): array|bool
    {
        $fromTs = is_numeric($from) ? (int)$from : strtotime((string)$from);
        $toTs = is_numeric($to) ? (int)$to : strtotime((string)$to);

        if (!$fromTs || !$toTs) {
            return false;
        }

        $fromTs += 1;

        // Monobank allows maximum 31 days + 1 hour window (2682000 seconds)
        $maxWindow = 2682000;
        if (($toTs - $fromTs) > $maxWindow) {
            $fromTs = $toTs - $maxWindow;
        }

        $account = trim($account);
        if (empty($account)) {
            $account = '0';
        }

        $request = self::request("/personal/statement/{$account}/{$fromTs}/{$toTs}", $token);

        return $request;
    }

    /**
     * Find FOP account from client info (by IBAN or first FOP account).
     */
    public static function findFopAccount(?string $token = null, ?string $iban = null): ?array
    {
        $clientInfo = self::getClientInfo($token);
        if (!$clientInfo || empty($clientInfo['accounts'])) {
            return null;
        }

        $cleanIban = $iban ? strtoupper(str_replace(' ', '', $iban)) : null;

        // 1. Try matching by IBAN
        if ($cleanIban) {
            foreach ($clientInfo['accounts'] as $account) {
                $accIban = strtoupper(str_replace(' ', '', $account['iban'] ?? ''));
                if ($accIban === $cleanIban) {
                    return $account;
                }
            }
        }

        // 2. Try finding by type = 'fop' (prefer UAH - currencyCode 980)
        $fopAccounts = array_filter($clientInfo['accounts'], fn($a) => ($a['type'] ?? '') === 'fop');
        if (!empty($fopAccounts)) {
            foreach ($fopAccounts as $account) {
                if (($account['currencyCode'] ?? 0) === 980) {
                    return $account;
                }
            }
            return reset($fopAccounts);
        }

        return null;
    }

    /**
     * Perform HTTP GET request to Monobank API.
     */
    private static function request(string $endpoint, ?string $token = null): array|bool
    {
        $uri = "https://api.monobank.ua" . $endpoint;
        $client = new Client();
        $token = $token ?? env("API_KEY_MONOBANK");

        if (!$token) {
            return false;
        }

        try {
            $response = $client->request('GET', $uri, [
                'headers' => [
                    'X-Token' => $token,
                ],
                'timeout' => 15,
            ]);
            $body = $response->getBody()->getContents();
            return json_decode($body, true);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $statusCode = $e->getResponse()?->getStatusCode();
            $body = $e->getResponse()?->getBody()?->getContents();
            Log::warning("Monobank API ClientException [{$statusCode}] on {$endpoint}: {$body}");
            return false;
        } catch (\Exception $e) {
            Log::error("Monobank API Exception on {$endpoint}: " . $e->getMessage());
            return false;
        }
    }
}
