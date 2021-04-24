<?php

namespace Devkeita\Laraliff;

use Devkeita\Laraliff\Services\LiffVerificationService;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWT;

class JWTGuard extends \Tymon\JWTAuth\JWTGuard
{
    private $verificationService;

    public function __construct(JWT $jwt, UserProvider $provider, Request $request, LiffVerificationService $verificationService)
    {
        parent::__construct($jwt, $provider, $request);
        $this->verificationService = $verificationService;
    }

    public function attempt(array $credentials = [], $login = true)
    {
        $liff = $this->verificationService->verify($credentials['liff_id_token']);
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
