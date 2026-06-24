<?php

declare(strict_types=1);

namespace App\Models\Wechat\Pay;

use App\Models\Wechat\Kernel\Support\Str;
use App\Models\Wechat\Pay\Contracts\Merchant as MerchantInterface;

class URLSchemeBuilder
{
    public function __construct(protected MerchantInterface $merchant)
    {
    }

    /**
     * @throws \Exception
     */
    public function forProduct(string | int $productId, string $appId): string
    {
        $params = [
            'appid' => $appId,
            'mch_id' => $this->merchant->getMerchantId(),
            'time_stamp' => time(),
            'nonce_str' => Str::random(),
            'product_id' => $productId,
        ];

        $params['sign'] = (new LegacySignature($this->merchant))->sign($params);

        return 'weixin://wxpay/bizpayurl?'.http_build_query($params);
    }

    public function forCodeUrl(string $codeUrl): string
    {
        return \sprintf('weixin://wxpay/bizpayurl?sr=%s', $codeUrl);
    }
}
