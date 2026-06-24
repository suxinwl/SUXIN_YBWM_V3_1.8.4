<?php

use Illuminate\Support\Env;
use Illuminate\Support\Facades\Facade;

return [
    'domain' => env("APP_URL", ''),

    'shdUploadUrl' => 'https://shd.y-bei.cn/',

    'authorizeDomain' => 'https://shouqan.y-bei.cn',

    'authType' => 1,

    'remoteUrl' => 'ybwn-v3.oss-cn-beijing.aliyuncs.com',

    'appKey' => 'ybwm',
    "smsDev" => env("smsDebug", true),

    'isDev' => false,
    'isWQ' => false,
    'smsConfig' => array(),
    'fubei' => [
        'vendor_sn' => '2022062015552336377a',
        "secret" => '07efa97e719eb1d175a8d8f2ca3fdf67',
        "agentId" => "95254"
    ],
    'xuixingfu' => [
        'privateKey' => "MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQDdWC8wKxk8I3INWtNM/8qwOK1NG6JsPrVfpie2iZabL6UkW7mHxE/56XRRmljvstAtkRvrGLKswWQ/ZN/TGDuNoezcrwAUvplu6+hO0zTEU7As1f4VRCrhQrHsxo/L76zWKZI3RDgjJepKODti15W4f5SPuK/YHeuXoOxwm9FIabYLhypEzvAakUrOGZIxcRTGb2tXmZQ9fmpop3ch31zmtustXebgJ0KDJ4ZklkISqx2EIsCzXQxL+GKtxsfbr5y+BOr/0e1U2cur19XoQN7FrgI7Iha+dKqvc+9gk8HB941HO4gbhNBGIj6OhMddQLqd0fZGm/88igm8mtZiVEGxAgMBAAECggEAFmRjcbYKeYEEesFjevitoqI5NgHDrruxUZnXjqngqJZrWIBHBqsfhCLP96lrseQfF10EvAXlnYB7CcbEtfBPpgZplfHGSlL15rjK6Z6ISgxFWGPVroUU6XD72v5DcdgvXgManaizHSsqxpNlvpwcs2uEtf1zHKP6P36yLLo2s+J9s3/3/7ibW3anoQL6xE/BUkqLJt9y/T4Vb3xgazKtYlbXl8WuH7p4oVKF6afpnXw/YZCAq1JYmz/zsx2FAuufxMftlw/QvFpYIxj4yG0eeM5ah9hNi3ETcyLT4dh7vievmr+H5NAjvxHhSF/WKxmQ/GKLoIL90UMii9Q3Mp1wAQKBgQDw1bWEuXNXqoNY9fDESZ56BMkAPDst/HRHCxOghyhUCw1J933LqNCZp/M6ayD9j/pW8aKSmoHuSrXPh5MtW5Wz7wr65PX/BLfe1Cy8fIxVHbWyJfIUOH0a8S4vAobf1X2vDiYiUxM5as+nc93abigs/Eu9hJHRNPzgWI6/6wLPgQKBgQDrSEnT5CSR3vLftZ3O79V5218By37DDS4tCQYOr5m+c44QKJ7uwe8WXnEaKFcZ1Bc4LVc971jXg6vLovMdsDAL2lcOfkUyL+NarXX5Kf2ihn/x8CoqEn35r/mlUA77xPWDnyF/QP1/uEbqa3aP14132v9jCUtYQvpJoi8H6m+KMQKBgQC1j3dz4tdYzNyOsYLch9+of3kE62N2DK+ga3JVf+9gRKC1FZbJdbAlVt9gOCk731JMP4hfW4n+mmYsWToUZMocR2cQtJHburPfkjdTtdWZyXcUIdU5d0ihihdWK2KA1pMU6ObI07ZXf/WieRBUvt0c5Os4qfvAK2FExJ6BguuwgQKBgGJ2E/9KgEtTQ8x+0pWhJHMkbLPxlxDFWUebeR94ORzMeu0kMq60FfwEdcx+iUTTzwvBXbsbiNBX1/MWNCt+afzr2HbGPOrtw3VVFgO5oNz88FotKVgF+RYeoJif0kVmfWAhngEFD5D9ax/67NjxWdCIo0usvg0nqlpaNthXMWphAoGBALS4CnDvqm4+C4UShv6f6znX2rlHL/+Al96+OZCd+f/GsW2l0+cyoPysRH7vpbrNnYUC9xSnnT2B+e2yQabKeSHFBlIifov24sct5xi21FGaj6WBq1ctFHSRvf5Azc4BuZDx7VF3fRtBlI2dBpDiSzI8aXZdDYg8gSShl183xrBf",
        'sxfPublic' => "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAjo1+KBcvwDSIo+nMYLeOJ19Ju4ii0xH66ZxFd869EWFWk/EJa3xIA2+4qGf/Ic7m7zi/NHuCnfUtUDmUdP0JfaZiYwn+1Ek7tYAOc1+1GxhzcexSJLyJlR2JLMfEM+rZooW4Ei7q3a8jdTWUNoak/bVPXnLEVLrbIguXABERQ0Ze0X9Fs0y/zkQFg8UjxUN88g2CRfMC6LldHm7UBo+d+WlpOYH7u0OTzoLLiP/04N1cfTgjjtqTBI7qkOGxYs6aBZHG1DJ6WdP+5w+ho91sBTVajsCxAaMoExWQM2ipf/1qGdsWmkZScPflBqg7m0olOD87ymAVP/3Tcbvi34bDfwIDAQAB",
        'orgId' => '91364657',
    ],
    'waisongbang' => [
        'dev' => [
            'app_key' => "egYbkndCUGmX5olg",
            'app_secret' => "0c898c19-84aa-436b-b27c-95c002643364",
            'url' => "https://beta7.waisongbang.com"
        ],
        'rel' => [
            'app_key' => "Gr6jjWU48anJGNTg",
            'app_secret' => "6630df3b-c1ec-4cfe-8c33-ae28a11df6d6",
            'url' => "https://e.waisongbang.com"
        ]
    ],
    'channelPath' => array(
        'admin' => '/super',
        'wechat' => '/h5',
        'channel' => '/admin'
    ),

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'YbwmV3'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'PRC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'zh_CN',

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'zh_CN',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => 'file',
        // 'store'  => 'redis',
    ],

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => [

        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,
        /*
         * Package Service Providers...
         */

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        Tymon\JWTAuth\Providers\LaravelServiceProvider::class,
        Overtrue\LaravelLang\TranslationServiceProvider::class,
        Fruitcake\Cors\CorsServiceProvider::class,
        SimpleSoftwareIO\QrCode\QrCodeServiceProvider::class,
        Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class,
        GrahamCampbell\Exceptions\ExceptionsServiceProvider::class,
        Hhxsv5\LaravelS\Illuminate\LaravelSServiceProvider::class,
        Intervention\Image\ImageServiceProvider::class,
        Milon\Barcode\BarcodeServiceProvider::class
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => Facade::defaultAliases()->merge([
        'App' => Illuminate\Support\Facades\App::class,
        'Arr' => Illuminate\Support\Arr::class,
        'Artisan' => Illuminate\Support\Facades\Artisan::class,
        'Auth' => Illuminate\Support\Facades\Auth::class,
        'Blade' => Illuminate\Support\Facades\Blade::class,
        'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
        'Bus' => Illuminate\Support\Facades\Bus::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'Config' => Illuminate\Support\Facades\Config::class,
        'Cookie' => Illuminate\Support\Facades\Cookie::class,
        'Crypt' => Illuminate\Support\Facades\Crypt::class,
        'Date' => Illuminate\Support\Facades\Date::class,
        'DB' => Illuminate\Support\Facades\DB::class,
        'Eloquent' => Illuminate\Database\Eloquent\Model::class,
        'Event' => Illuminate\Support\Facades\Event::class,
        'File' => Illuminate\Support\Facades\File::class,
        'Gate' => Illuminate\Support\Facades\Gate::class,
        'Hash' => Illuminate\Support\Facades\Hash::class,
        'Http' => Illuminate\Support\Facades\Http::class,
        'Lang' => Illuminate\Support\Facades\Lang::class,
        'Log' => Illuminate\Support\Facades\Log::class,
        'Mail' => Illuminate\Support\Facades\Mail::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,
        'Password' => Illuminate\Support\Facades\Password::class,
        'Queue' => Illuminate\Support\Facades\Queue::class,
        'RateLimiter' => Illuminate\Support\Facades\RateLimiter::class,
        'Redirect' => Illuminate\Support\Facades\Redirect::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
        'Request' => Illuminate\Support\Facades\Request::class,
        'Response' => Illuminate\Support\Facades\Response::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'Session' => Illuminate\Support\Facades\Session::class,
        'Storage' => Illuminate\Support\Facades\Storage::class,
        'Str' => Illuminate\Support\Str::class,
        'URL' => Illuminate\Support\Facades\URL::class,
        'Validator' => Illuminate\Support\Facades\Validator::class,
        'View' => Illuminate\Support\Facades\View::class,
        'JWTAuth' => Tymon\JWTAuth\Facades\JWTAuth::class,
        'JWTFactory' => Tymon\JWTAuth\Facades\JWTFactory::class,
        'Sms' => iBrand\Sms\Facade::class,
        'EasyWeChat' => Overtrue\LaravelWeChat\Facade::class,
        'QrCode' => SimpleSoftwareIO\QrCode\Facades\QrCode::class,
        'Image' => Intervention\Image\Facades\Image::class,
    ])->toArray(),
];
