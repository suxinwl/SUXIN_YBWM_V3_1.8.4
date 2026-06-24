<?php

declare(strict_types=1);

namespace App\Models\Wechat\Make\Contracts;

interface Account
{
    public function getBaseUri(): string;
    public function getAppId(): string;

    public function getToken(): string;
}
