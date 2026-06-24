<?php

namespace App\Http\Controllers\Channel;

use App\Http\Controllers\Channel\ApiController;
use App\Models\MiniVersion;
use App\Models\OpenWecahtVersion;
use App\Models\OpenWechatAuth;
use App\Services\ConfigService;
use App\Services\OpenWechat\AdminOpenWechat;
use Illuminate\Support\Facades\Request;

class OpenWechatController extends ApiController
{
    public function getAuthorizationUrl()
    {
        $url = AdminOpenWechat::getAuthorizationUrl(url('common/openWechat/auth/' . $this->uniacid()), Request()->type ?: 1);
        return $this->success($url);
    }

    public function config()
    {
        if (Request()->type == 1) {
            return $this->success(ConfigService::officialConfig($this->uniacid()));
        }
        return $this->success(ConfigService::miniConfig($this->uniacid()));
    }

    /**
     * 切换配置渠道
     */
    public function switch()
    {
        if (Request()->type == 2) {
            return $this->success(ConfigService::officialSwitch($this->uniacid()));
        }
        return $this->success(ConfigService::miniSwitch($this->uniacid()));
    }

}
