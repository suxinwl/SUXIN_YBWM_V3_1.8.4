<?php

declare(strict_types=1);

namespace App\Models\Wechat\WaiSongBang\Contracts;

use App\Models\Wechat\Kernel\Contracts\Config;
use App\Models\Wechat\WaiSongBang\Contracts\Merchant;
use Symfony\Contracts\HttpClient\HttpClientInterface;

interface Application
{
    public function getMerchant(): Merchant;
    public function getConfig(): Config;
    public function getHttpClient(): HttpClientInterface;
    public function getClient(): HttpClientInterface;
}
