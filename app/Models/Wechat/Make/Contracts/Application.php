<?php

declare(strict_types=1);

namespace App\Models\Wechat\Make\Contracts;

use App\Models\Wechat\Kernel\Contracts\AccessToken;
use App\Models\Wechat\Kernel\Contracts\Config;
use App\Models\Wechat\Kernel\Contracts\Server;
use App\Models\Wechat\Kernel\Encryptor;
use App\Models\Wechat\Kernel\HttpClient\AccessTokenAwareClient;
use App\Models\Wechat\Make\Contracts\Account;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

interface Application
{
    public function getAccount(): Account;
    public function getRequest(): ServerRequestInterface;

    public function getClient(): AccessTokenAwareClient;

    public function getHttpClient(): HttpClientInterface;

    public function getConfig(): Config;

    public function getAccessToken(): AccessToken;
    public function getCache(): CacheInterface;
}
