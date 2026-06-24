<?php

declare(strict_types=1);

namespace App\Models\Wechat\Kernel\Contracts;

interface Jsonable
{
    public function toJson(): string|false;
}
