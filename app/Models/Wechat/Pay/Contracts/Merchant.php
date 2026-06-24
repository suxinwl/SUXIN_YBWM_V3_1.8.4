<?php

declare(strict_types=1);

namespace App\Models\Wechat\Pay\Contracts;

use App\Models\Wechat\Kernel\Support\PrivateKey;
use App\Models\Wechat\Kernel\Support\PublicKey;

interface Merchant
{
    public function getMerchantId(): int;
    public function getPrivateKey(): PrivateKey;
    public function getSecretKey(): string;
    public function getV2SecretKey(): ?string;
    public function getCertificate(): PublicKey;
    public function getPlatformCert(string $serial): ?PublicKey;
}
