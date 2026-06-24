<?php

declare(strict_types=1);

namespace App\Models\Wechat\WaiSongBang\Contracts;

use App\Models\Wechat\Kernel\Support\PrivateKey;
use App\Models\Wechat\Kernel\Support\PublicKey;

interface Merchant
{
    public function getMerchantId(): int;
    public function getAppKey(): string;
    public function getSecretKey(): string;
    public function getThirdPartnerId(): int;
}
