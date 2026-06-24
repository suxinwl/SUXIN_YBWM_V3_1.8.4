<?php

namespace App\Http\Controllers\ChannelApi;

use App\Http\Resources\ChannelApi\Store\StoreList;
use App\Models\Collect;
use App\Models\Order\OrderIndex;
use App\Models\Store;
use App\Models\StoredValue;
use App\Models\StoredValueOrder;
use App\Services\ConfigService;
use App\Services\StoreGeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class StoreValueController extends ApiController
{
    public function index(Request $request)
    {
        try {
            $list = StoredValue::where("uniacid", $this->uniacid())
                ->where('state', 1)->orderBy('sort', 'asc')
                ->get();
            return $this->success($list);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->failed($e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            OrderIndex::where('type', 2)->where('uniacid', $this->uniacid())
                ->where('userId', $this->userId())->where('state', 1)->delete();
            StoredValueOrder::where('uniacid', $this->uniacid())
                ->where('userId', $this->userId())->where('state', 1)->delete();
            if ($request->id == 0) {
                if ($request->money <= 0) {
                    return $this->failed('最低充值金额不能为0');
                }
                $model = new StoredValue([
                    'amount' => $request->money,
                    "uniacid" => $this->uniacid(),
                    'userId' => $this->userId(),
                    'storeId' => $this->storeId(),
                    'storeValueId' => 0,
                    'score' => $this->appType()
                ]);
            } else {
                $model = StoredValue::where('uniacid', $this->uniacid())->find($request->id);
                if (empty($model)) {
                    return $this->failed('数据不存在');
                }
            }
            $order = StoredValueOrder::create([
                "uniacid" => $this->uniacid(),
                'userId' => $this->userId(),
                'storeId' => $this->storeId(),
                'orderSn' => getTakeOutNo(),
                'money' => $model->amount,
                'data' => collect($model->rule)->toArray(),
                'state' => 1,
                'score' => $this->appType(),
                'expiredTime' => date("Y-m-d H:i:s", time() + 60 * 15),
                'storeValueId' => $request->id
            ]);
            return $this->success(['orderSn' => $order->orderSn]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->failed($e->getMessage());
        }
    }

    public function order(Request $request)
    {
        try {
            $list = StoredValue::where("uniacid", $this->uniacid())
                ->where("userId", $this->userId())
                ->where("state", 2)
                ->paginate($request->size ?? 10, '*', 'page');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->failed($e->getMessage());
        }
    }
}
