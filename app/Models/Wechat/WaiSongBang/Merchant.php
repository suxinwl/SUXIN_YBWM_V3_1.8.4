<?php

declare(strict_types=1);

namespace App\Models\Wechat\WaiSongBang;

use App\Models\Wechat\WaiSongBang\Contracts\Merchant as MerchantInterface;


class Merchant implements MerchantInterface
{
    public function __construct(
        protected int | string $mchId,
        protected ?string $appkey = null,
        protected ?string $secretKey = null,
        protected ? int $thirdPartnerId = null,
        protected $ext = [],
    ) {
    }
    public function getMerchantId(): int
    {
        return \intval($this->mchId);
    }
    public function getAppKey(): string
    {
        return $this->appkey;
    }
    public function getSecretKey(): string
    {
        return $this->secretKey;
    }
    public function getExt(): array
    {
        return $this->ext;
    }
    public function getThirdPartnerId(): int
    {
        return $this->thirdPartnerId;
    }
}
