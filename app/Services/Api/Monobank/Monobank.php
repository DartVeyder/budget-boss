<?php

namespace App\Services\Api\Monobank;

use GuzzleHttp\Client;

class Monobank
{

    static public function getStatement($from,$to):array|bool
    {
        $from = strtotime($from) + 1;
        $to = strtotime($to);

        $request = self::request("/personal/statement/0/{$from}/{$to}");

        return $request ;
    }
    static private function request(string $endpoint) :array|bool
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
            return json_decode( $body,1) ;
        } catch (\Exception $e) {
            // Обробка помилок
            return  false;
        }
    }
}
