<?php

declare(strict_types=1);

namespace App\Models\Wechat\Kernel\Contracts;

use Psr\Http\Message\ResponseInterface;

interface Server
{
    public function serve(): ResponseInterface;
}
