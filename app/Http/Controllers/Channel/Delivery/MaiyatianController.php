<?php

namespace App\Http\Controllers\Channel\Delivery;

use App\Http\Controllers\Channel\ApiController;
use App\Models\Admin\Apply;
use App\Models\Delivery\Rule;
use App\Models\Delivery\Channel;
use App\Models\Wechat\Maiyatian\Application;
use App\Services\Delivery\MaiyatianService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class MaiyatianController extends ApiController
{

    public function citys(Request $request)
    {
        $list = Cache::get('maiyatianCity', false);
        if (!$list) {
            $app = MaiyatianService::app($this->uniacid());
            $list = $app->getClient()->postJson('/city/gets', ['json' => []])->toArray(false);
            if ($list['status'] != 1) {
                return $this->failed($list['msg']);
            }
            Cache::set("maiyatianCity", $list);
        }
        return $this->success($list['data']);
    }


    public function district(Request $request)
    {
        $list = Cache::get('maiyatianDistrict', false);
        if (!$list) {
            $app = MaiyatianService::app($this->uniacid());
            $list = $app->getClient()->postJson('/district/gets', ['json' => []])->toArray(false);
            if ($list['status'] != 1) {
                return $this->failed($list['msg']);
            }
            Cache::set("maiyatianDistrict", $list);
        }
        return $this->success($list['data']);
    }

    public function category(Request $request)
    {
        $data = [
            ["id" => 1, "label" => "食品"],
            ["id" => 2, "label" => "饮品"],
            ["id" => 3, "label" => "鲜花"],
            ["id" => 4, "label" => "票务"],
            ["id" => 5, "label" => "超市"],
            ["id" => 6, "label" => "水果"],
            ["id" => 7, "label" => "医药"],
            ["id" => 8, "label" => "蛋糕"],
            ["id" => 9, "label" => "酒品"],
            ["id" => 10, "label" => "服装"],
            ["id" => 11, "label" => "汽配"],
            ["id" => 12, "label" => "数码"],
            ["id" => 13, "label" => "夜宵烧烤"],
            ["id" => 14, "label" => "水产"],
            ["id" => 15, "label" => "百货"],
            ["id" => 99, "label" => "其他"]
        ];
        return $this->success($data);
    }


    public function  store(Request $request)
    {
        $apply =  Apply::find($this->uniacid());
        if (!$apply) {
            return $this->failed('数据不存在');
        }
        $list = Cache::get("applyMytId" . $this->uniacid(), false);
        if (!$list) {
            $app = MaiyatianService::app($this->uniacid());
            $list = $app->getClient()->postJson('/channel/shop/save/', ['json' => [
                'origin_id' => 'shopid' . $this->uniacid(),
                'name' => $apply->applyName,
                'city' => $request->city,
                'district' => $request->district,
                'phone' => $request->phone,
                'address' => $request->address,
                'longitude' => $request->longitude,
                'latitude' => $request->latitude,
                'category' => $request->category,
                'map_type' => 1
            ]])->toArray(false);
            if ($list['status'] != 1) {
                return $this->failed($list['msg']);
            }
            cache::set("applyMytId" . $this->uniacid(), $list);
        }
        return $this->success($list['data']);
    }


    public function h5(Request $request, $id)
    {
        $model =  Channel::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            return $this->failed('请先授权麦芽田');
        }
        if ($this->uniacid()) {
            $app = MaiyatianService::app($this->uniacid());
        } else {
            $app = MaiyatianService::storeApp($this->storeId(),$this->uniacid());
        }
        $body = array(
            'app_key' => $app->getMerchant()->getAppKey(),
            'params' => [],
            'timestamp' => time(),
            'version' => '1'
        );
        $shop_id=$this->storeId()?$model->channelId:'shopid'.$this->uniacid();
        $url = "https://m.maiyatian.com/router?route_module=setting&app_key={$app->getMerchant()->getAppKey()}&shop_id={$shop_id}&sign={$app->getClient()->createSignature($body)}";
        $url =  QrCode::format('png')->size(200)->generate($url);    //format 是指定生成文件格式  默认格式是svg,可以直接在浏览器打开，png不能直接显示
        $url = 'data:image/png;base64,' . base64_encode($url);
        return $this->success($url);
    }
}
