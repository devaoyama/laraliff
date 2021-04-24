<?php

namespace Devkeita\Laraliff;

use Devkeita\Laraliff\Services\LiffVerificationService;

class JWTGuard extends \Tymon\JWTAuth\JWTGuard
{
    public function attempt(array $credentials = [], $login = true)
    {
        $verificationService = new LiffVerificationService(); // Todo DIを使う
        $liff = $verificationService->verify($credentials['liff_id_token']);
        if (!$user = $this->provider->retrieveByLiffId($liff['sub'])) {
            return false;
        }
        $user->update([
            config('laraliff.fields.name') => $liff['name'],
            config('laraliff.fields.picture') => $liff['picture']
        ]);

        return $login ? $this->login($user) : true;
    }
}
