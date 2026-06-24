<?php

declare(strict_types=1);

namespace App\Models\Wechat\Maiyatian\Contracts;

use App\Models\Wechat\Kernel\Contracts\Config;
use App\Models\Wechat\Maiyatian\Contracts\Merchant;
use Symfony\Contracts\HttpClient\HttpClientInterface;

interface Application
{
    public function getMerchant(): Merchant;
    public function getConfig(): Config;
    public function getHttpClient(): HttpClientInterface;
    public function getClient(): HttpClientInterface;
}
