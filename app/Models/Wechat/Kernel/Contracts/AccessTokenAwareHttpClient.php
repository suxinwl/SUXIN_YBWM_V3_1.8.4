<?php

declare(strict_types=1);

namespace App\Models\Wechat\Kernel\Contracts;

use App\Models\Wechat\Kernel\Contracts\AccessToken as AccessTokenInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

interface AccessTokenAwareHttpClient extends HttpClientInterface
{
    public function withAccessToken(AccessTokenInterface $accessToken): static;
}
