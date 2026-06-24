<?php

namespace App\Http\Controllers\Channel\Delivery;

use App\Http\Controllers\Channel\ApiController;
use App\Models\Delivery\Rule;
use App\Models\Delivery\Channel;
use App\Models\Delivery\Order;
use App\Models\Region;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use App\Models\ReallysavesMoney;
use App\Models\Store;

class ReallysavesMoneyController extends ApiController
{
    //获取城市列表
    public function getCity(Request $request)
    {
        $list = Region::where('level', 1)
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }
    //新增发货店铺
    public function store(Request $request)
    {
        $storeInfo = Store::where('storeId', $request->storeId)->first();
        $storeInfo = empty($storeInfo) ? array() : $storeInfo->toArray();
        $model = Channel::where('uniacid', $this->uniacid())->where('storeId', $request->storeId)->first();
        if ($model) {
            return $this->success([], '添加成功');
        }
        ReallysavesMoney::createApplication($storeInfo['name'] . '-' . $storeInfo['id'], $storeInfo['contact'], $callbackUrl);
        $data = ReallysavesMoney::createStore($storeInfo, $request->industryType, $request->name);
        $data = json_decode($data, true);
        Channel::create([
            'uniacid' => $this->uniacid(),
            'config' => $request->config,
            'storeId' => $request->input('storeId'),
            'type' => 3
        ]);
        if ($data['code'] == 200) {
            return $this->success([], '添加成功');
        } else {
            return $this->failed($data['message']);
        }
    }

    public function category(Request $request)
    {
        $data = [
            ["id" => 1, "label" => "餐饮"],
            ["id" => 2, "label" => "鲜花"],
            ["id" => 3, "label" => "蛋糕"],
            ["id" => 4, "label" => "商超"],
            ["id" => 5, "label" => "医药"],
            ["id" => 6, "label" => "母婴"],
            ["id" => 7, "label" => "服饰"],
            ["id" => 8, "label" => "数码电子"],
            ["id" => 9, "label" => "其他"]
        ];
        return $this->success($data);
    }
}
