<?php

namespace Devkeita\Laraliff;

use Devkeita\Laraliff\Services\LiffVerificationService;

class JWTGuard extends \Tymon\JWTAuth\JWTGuard
{
    public function attempt(array $credentials = [], $login = true, $update = false)
    {
        $verificationService = new LiffVerificationService(); // Todo DIを使う
        $liff = $verificationService->verify($credentials['liff_id_token']);
        if (!$user = $this->provider->retrieveByLiffId($liff['sub'])) {
            return false;
        }
        if ($update) {
            $user->update(['name' => $liff['name'], 'picture' => $liff['picture']]);
        }

        return $login ? $this->login($user) : true;
    }
}
