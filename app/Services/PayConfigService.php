<?php

namespace App\Services;

use App\Models\PayConfig;

class PayConfigService extends BaseService
{
    public static function getConfig($channel, $appType, $uniacid = 0)
    {
        $data = [
            "weixin" => 0,
            "alipay" => 0,
            "balance" => 0,
            'default' => 0,
        ];
        $list = PayConfig::with(['payTemplate'])->where(function ($q) use ($uniacid, $channel) {
            $q->where('uniacid', $uniacid);
            if ($channel) {
                $q->where('channel', $channel);
            }
        })->orderBy('id', 'desc')->get();
        if (!empty($list)) {
            $data['default']=0;
            foreach ($list as $key => $v) {
                $data['default'] = $v->isDefault == 1 ? $v->payType : $data['default'];
                if ($v->payType == 0) {
                    $data['balance'] = $v->templateId;
                } elseif ($v->payType == 1) {
                    $data['weixin'] = $v->templateId;
                } elseif ($v->payType == 2) {
                    $data['alipay'] = $v->templateId;
                }
            }
        }
        return $data;
    }
}
