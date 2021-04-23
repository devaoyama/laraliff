# laraliff

## 概要

- [LIFFアプリ](https://developers.line.biz/ja/docs/liff/overview/)の認証をするためのライブラリ
- [tymondesigns/jwt-auth](https://github.com/tymondesigns/jwt-auth)のラッパーライブラリ

## laraliffでできること

1. LIFFの[IDトークン](https://developers.line.biz/ja/docs/liff/using-user-profile/#%E3%83%A6%E3%83%BC%E3%82%B5%E3%82%99%E3%83%BC%E6%83%85%E5%A0%B1%E3%82%92%E3%82%B5%E3%83%BC%E3%83%8F%E3%82%99%E3%83%BC%E3%81%A6%E3%82%99%E4%BD%BF%E7%94%A8%E3%81%99%E3%82%8B)利用して、サーバーサイドで認証
2. 一度認証できたら、それ移行はJWTで認証を行う

## 使い方

#### [tymondesigns/jwt-auth](https://github.com/tymondesigns/jwt-auth)のconfigを作成

```sh
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
```

#### laraliffのconfigを作成

```sh
php artisan vendor:publish --provider="Devkeita\Laraliff\Providers\LaraliffServiceProvider"
```

#### JWT secret keyを発行

```sh
php artisan jwt:secret
```

#### .envに`LIFF_CHANNEL_ID`を追加

```
...
LIFF_CHANNEL_ID=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

#### 認証に使用するテーブルのスキーマに以下を追加
- `liff_id`
  - LIFF ID
- `name`
  - LINEのプロフィール名
- `picture`
  - プロフィール画像のURL

```php:create_user.php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('liff_id')->unique();
            $table->string('name');
            $table->text('picture');
            $table->timestamps();
        });
    }
    ...
}

```

#### 認証に使用するモデルに以下のメソッドを追加

```php:User.php
namespace App;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    // Rest omitted for brevity

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
```

#### `config/auth.php`を修正

```php:auth.php
'defaults' => [
    'guard' => 'api',
    'passwords' => 'users',
],

...

'guards' => [
    'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
],
```

#### 認証用のrouteを追加

```php:route.php
Route::group([

    'middleware' => 'api',
    'prefix' => 'auth'

], function ($router) {

    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('me', 'AuthController@me');

});
```

#### 認証用のコントローラーを作成
```php:Auth.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\User;
use Devkeita\Laraliff\Services\Exceptions\LiffUnverfiedException;
use Devkeita\Laraliff\Services\LiffVerificationService;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    public function register(LiffVerificationService $verificationService)
    {
        try {
            $liff = $verificationService->verify(request('token'));
        } catch (LiffUnverfiedException $e) {
            return response()->json(['error' => 'LIFF ID Token is unauthorized'], 401);
        }

        $user = User::create([
            'liff_id' => $liff['sub'],
            'name' => $liff['name'],
            'picture' => $liff['picture'],
        ]);

        return response()->json(auth('api')->login($user));
    }

    public function login()
    {
        try {
            $jwt = auth('api')->attempt(request(['liff_id_token']));
        } catch (LiffUnverfiedException $e) {
            return response()->json(['error' => 'LIFF ID Token is unauthorized'], 401);
        }
        if (!$jwt) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json($jwt);
    }

    public function me()
    {
        return response()->json(auth('api')->user());
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }
}
```
