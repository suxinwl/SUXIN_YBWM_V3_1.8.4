<?php

declare(strict_types=1);

namespace App\Models\Wechat\WaiSongBang;

use App\Models\Wechat\Kernel\Support\Str;
use App\Models\Wechat\WaiSongBang\Contracts\Merchant as merchantInterface;
use Nyholm\Psr7\Uri;

class Signature
{
    public function __construct(protected merchantInterface $merchant)
    {
    }

    /**
     * @param  array<string,mixed>  $options
     *
     * @throws \Exception
     */
    public function createHeader($array): string
    {
        ksort($array);
        $sign = '';
        foreach ($array as $k => $v) {
            $sign .= $k . $v;
        }
        $sign .= $this->merchant->getSecretKey();
        $sign = Md5($sign);
        return $sign;
    }
}
