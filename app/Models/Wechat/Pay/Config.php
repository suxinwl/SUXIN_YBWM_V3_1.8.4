<?php

declare(strict_types=1);

namespace App\Models\Wechat\Pay;

class Config extends \App\Models\Wechat\Kernel\Config
{
    /**
     * @var array<string>
     */
    protected array $requiredKeys = [
        'mch_id',
        'secret_key',
        'private_key',
        'certificate',
    ];
}
