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
use App\Models\Drag;
use App\Models\OrderCollect\OrderCollect;
use App\Models\Store\Notice;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class DragController extends ApiController
{

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($this->storeId()) {
            $key = 'drag:' . $this->uniacid() . 'storeId:' . $this->storeId();
        } else {
            $key = 'drag:' . $this->uniacid();
        }
        if (!Cache::has($key)) {
            $drag = Drag::where("uniacid", $this->uniacid())
                ->where('state', 1)
                ->where('storeId', $this->storeId())
                ->whereIN('appType', [1, 2, 4, 5, 6, 7, 8])
                ->get();
            $drag = collect($drag)->mapWithKeys(function ($item, $key) {
                $item  = collect($item)->toArray();
                switch ($item['appType']) {
                    case 1:
                        $item['key'] = "index";
                        break;
                    case 2:
                        $item['key'] = "user";
                        break;
                    case 4:
                        $item['key'] = "goods";
                        break;
                    case 5:
                        $item['key'] = "nav";
                        break;
                    case 6:
                        $item['key'] = "style";
                        break;
                    case 7:
                        $item['key'] = $item['type'];
                        break;
                    case 8:
                        $item['key'] = 'goodStyle';
                        break;
                }
                return [$item['key'] => $item['data']];
            });
            Cache::set($key, $drag, 3600 * 24);
        }
        $apply = Apply::find($this->uniacid());
        $storeId = $this->isolateStore();
        $notes =   Notice::where('uniacid', $this->uniacid())
            ->where('startTime', "<=", date("Y-m-d H:i:s", time()))
            ->where('endTIme', ">=", date("Y-m-d H:i:s", time()))
            ->when($this->isolate(), function ($q) use ($storeId) {
                $q->where('type', 2)->whereHas('stores', function ($q) use ($storeId) {
                    return $q->where('storeId', $storeId);
                });
            })
            ->when(!$this->isolate(), function ($q) {
                $q->where('type', 1);
            })
            ->get();
        $orderCollect = OrderCollect::where("uniacid", $this->uniacid())
            ->where("startTime", '<=', date("Y-m-d H:i:s", time()))
            ->where("endTime", '>=', date("Y-m-d H:i:s", time()))
            ->first();
        if ($orderCollect) {
            $orderCollect->setAppends([
                'userData', 'stateFormat'
            ]);
        }
        $drag = collect(Cache::get($key))->merge(collect(['copyright' => $apply->copyrightData, 'notice' => $notes, 'orderCollect' => $orderCollect]));
        return $this->success($drag);
    }
    public function show(Request $request, $id)
    {
        $drag = Drag::where("uniacid", $this->uniacid())->where('id', $id)->first();
        if (empty($drag)) {
            return $this->failed('自定义页面不存在');
        }
        return $this->success($drag);
    }
}
