<?php

namespace App\Http\Controllers\Channel;

use App\Http\Controllers\Channel\ApiController;
use App\Models\OpenWechatAuth;
use App\Services\OpenWechat\ChannelOpenWechat;
use Illuminate\Support\Facades\Route;

class OfficialController extends ApiController
{


    /**
     * 获取公众号授权版本信息
     */

    public function version()
    {
        $model = OpenWechatAuth::where('uniacid', $this->uniacid())->where('type', 'official')->first();
        $data = null;
        if ($model) {
            $data = json_decode(json_encode($model->data), true);
            $data['appid'] = $model->authorizer_appid;
        }
        return $this->success($data);
    }
}
