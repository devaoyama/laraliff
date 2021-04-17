<?php

namespace Devkeita\Laraliff\Services;

use Devkeita\Laraliff\Services\Exceptions\LiffUnverfiedException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class LiffVerificationService
{
    public function verify(string $token): array
    {
        $client = new Client();
        try {
            $response = $client->request('POST', 'https://api.line.me/oauth2/v2.1/verify', [
                'form_params' => [
                    'id_token' => $token,
                    'client_id' => config('laraliff.liff_channel_id'),
                ]
            ]);
            $liff = json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $exception) {
            throw new LiffUnverfiedException();
        }

        return $liff;
    }
}
