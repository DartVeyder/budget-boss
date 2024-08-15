<?php

namespace App\Services\Api\Monobank;

use GuzzleHttp\Client;

class Monobank
{
    static public function getStatement($from,$to):array
    {
        $from = strtotime($from);
        $to = strtotime($to);
        return self::request("/personal/statement/0/{$from}/{$to}");
    }
    static private function request(string $endpoint) :array|string
    {
        $uri = env("API_URL_MONOBANK"). $endpoint;
        $client = new Client();

        try {
            $response = $client->request('GET', $uri, [
                'headers' =>[
                    'X-Token' => env("API_KEY_MONOBANK"),
                ],
            ]);
            $body = $response->getBody()->getContents();
            return json_decode( $body) ;
        } catch (\Exception $e) {
            // Обробка помилок
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
