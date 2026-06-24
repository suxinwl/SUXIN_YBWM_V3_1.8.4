<?php

namespace App\Http\Controllers\ChannelApi;

use App\Models\BulkPackage;
use App\Models\PayConfig;
use App\Models\Region;
use App\Models\Shop;
use App\Models\Store;
use App\Services\ConfigService;
use App\Services\MapService;
use App\Services\OpenWechat\ChannelOpenWechat;
use App\Services\Pay\WechatPay;
use App\Services\PayService;
use Collator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Swoole\FastCGI\HttpRequest;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RegionController extends ApiController
{
    public function address(Request $request)
    {
        if (!empty($request->lat) && !empty($request->lng)) {
            $res = MapService::region($request->lat, $request->lng, $this->uniacid());
            if(empty($res)){
                throw new BadRequestException('获取定位地址失败');
            }
            $code = $res['code'];
            $address = $res['address'];
            $formatted_addresses = $res['formatted_addresses'];
            $region = Region::where('code', $code)->first();
            $region =  Region::find($region->pid);
            return $this->success([
                'address' => $address,
                'formatted_addresses' => $formatted_addresses,
                'region' => $region
            ]);
        }
        throw new BadRequestException('缺少定位参数');
    }

    public function index()
    {
        $model = Store::business()->where('uniacid', $this->uniacid())->get();
        if (empty($model)) {
            return $this->success();
        }
        $ids =  collect($model)->pluck('region')->collapse()->toarray();
        $list = Region::whereIn('id', $ids)->where('level', 1)->get();
        $list = collect($list)->groupBy('pinyin_prefix');
        return $this->success($list);
    }
}
