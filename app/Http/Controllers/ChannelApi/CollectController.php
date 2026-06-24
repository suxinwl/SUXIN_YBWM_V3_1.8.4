<?php

namespace App\Http\Controllers\ChannelApi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Channel\ConfigRequest;
use App\Models\ChannelConfig as Config;
use App\Models\ChannelConfig;
use App\Models\OpenWechatAuth;
use App\Services\ChannelConfigService;
use App\Services\ConfigService;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Ali;
use App\Models\Aliauth;
use App\Models\ShopAccount;
use App\Models\Admin\Apply;
use App\Models\Collect;
use App\Models\Drag;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CollectController extends ApiController
{

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $type)
    {
        $list = Collect::where("uniacid", $this->uniacid())->where("userId", $this->userId())->paginate($request->size ?? 10, '*', 'page');
    }


    public function store(Request $request, $type, $id)
    {
        $model = Collect::withTrashed()->where("uniacid", $this->uniacid())->where("userId", $this->userId())->where("type", $type)->where("collectId", $id)->first();
        if ($model) {
            if ($model->deleted_at) {
                $model->restore();
                return $this->success([], "已收藏");
            } else {
                $model->delete();
                return $this->success([], "已取消");
            }
        } else {
            $model = Collect::create(['uniacid' => $this->uniacid(), "type" => $type, "collectId" => $id, 'userId' => $this->userId()]);
            return $this->success([], "已收藏");
        }
    }
}
