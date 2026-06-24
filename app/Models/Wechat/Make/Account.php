<?php

declare(strict_types=1);

namespace App\Models\Wechat\Make;

use App\Models\Wechat\Make\Contracts\Account as AccountInterface;
use RuntimeException;

class Account implements AccountInterface
{
    public function __construct(
        protected string $baseUri,
        protected string $appId,
        protected ?string $token,
    ) {
    }
    public function getBaseUri(): string
    {
        return $this->baseUri;
    }
    public function getAppId(): string
    {
        return $this->appId;
    }

    public function getToken(): string
    {
        if (null === $this->token) {
            throw new RuntimeException('No token configured.');
        }
        return $this->token;
    }
}
