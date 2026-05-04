<?php

namespace App\Services\Api\Monobank;

use GuzzleHttp\Client;

class Monobank
{

    static public function getStatement($from,$to, $token = null):array|bool
    {
        $from = strtotime($from) + 1;
        $to = strtotime($to);

        $request = self::request("/personal/statement/0/{$from}/{$to}", $token);

        return $request ;
    }
    static private function request(string $endpoint, $token = null) :array|bool
    {
        $uri = "https://api.monobank.ua" . $endpoint;
        $client = new Client();
        $token = $token ?? env("API_KEY_MONOBANK");

        if (!$token) {
            return false;
        }

        try {
            $response = $client->request('GET', $uri, [
                'headers' =>[
                    'X-Token' => $token,
                ],
            ]);
            $body = $response->getBody()->getContents();
            return json_decode( $body,1) ;
        } catch (\Exception $e) {
            // Обробка помилок
            return  false;
        }
    }
}
