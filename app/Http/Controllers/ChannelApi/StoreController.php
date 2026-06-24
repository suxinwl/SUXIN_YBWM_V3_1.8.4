<?php

namespace App\Http\Controllers\ChannelApi;

use App\Http\Resources\ChannelApi\Store\StoreList;
use App\Models\Collect;
use App\Models\Store;
use App\Services\ConfigService;
use App\Services\StoreGeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class StoreController extends ApiController
{
    public function default(Request $request)
    {
        try {
            $config = ConfigService::getChannelConfig('storeBasicSetting', $this->uniacid());
            if (empty($config)) {
                return $this->success(null);
            }
            if ($config['goState'] == 3) {
                return $this->success($config['storeId']);
            }
            if ($config['goState'] == 1) {
                $default  = StoreGeoService::getRadius($this->uniacid(), $request->lat, $request->lng, $config['shopKm'], 'km', ["ASC", "COUNT" => 1]);
                if (empty($default)) {
                    return $this->success(null);
                }
                return $this->success($default[0]);
            }
            return $this->success(null);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->success(null);
        }
    }

    public function index(Request $request)
    {
        $config = ConfigService::getChannelConfig('storeBasicSetting', $this->uniacid());
        if ($config['pageState'] == 1 && empty($request->recharge)) {
            $km = $config['km'];
        } else {
            $km = 15000;
        }

        $storeIds  = StoreGeoService::getRadius($this->uniacid(), $request->lat, $request->lng, $km, 'km', ["ASC"]);
        $storeList=Store::where('uniacid',$this->uniacid())->where('differentPlacesSwitch',1)->get();
        if($storeList){
            $storeList = empty($storeList) ? array() : $storeList->toArray();
            $ids=array_column($storeList,'id');
            $storeIds=array_unique($storeIds+$ids);
        }
        if ($request->collect) {
            $collectList  = Collect::select(["collectId"])->where("uniacid", $this->uniacid())
                ->where("type", 'store')
                ->where('userId', $this->userId())
                ->get();
            if (empty($collectList)) {
                return $this->success([]);
            }
            $collectList = collect($collectList)->pluck('collectId')->all();
            $storeIds  = array_intersect($storeIds, $collectList);
        }
        if ($request->searchIds) {
            $ids = is_array($request->searchIds) ? $request->searchIds : explode(',', $request->searchIds);
            $storeIds  = array_intersect($storeIds, $ids);
        }
        if ($request->filtrIds) {
            $ids = is_array($request->filtrIds) ? $request->filtrIds : explode(',', $request->filtrIds);
            $storeIds  = array_diff($storeIds, $ids);
        }
        $list = Store::business()
            ->with(['collectStore', 'label'])->whereIn('id', $storeIds)
            ->when($request->regionId, function ($q) use ($request) {
                return $q->where('region', 'like', "%$request->regionId%");
            })
            ->when($request->cityId &&  $request->cityId != 'undefined' && $config['pageState'] == 2, function ($q) use ($request) {
                return $q->where('region', 'like', "%$request->cityId%");
            })
            ->when($request->keyword, function ($q) use ($request) {
                return $q->where('name', 'like', "%$request->keyword%");
            })
            ->where('isolate',0)
            ->orderByRaw("FIELD(`id`," . implode(',', $storeIds) . ")")
            ->paginate($request->size ?? 10, '*', 'page');
        return $this->success(new StoreList($list));
    }

    public function show(Request $request, $id)
    {
        $model = Store::business()->with(['collectStore'])->find($id);
        if (empty($model)) {
            throw new BadRequestHttpException('门店不存在或已停业');
        }
        $model->setAppends(['distance', 'distanceNum','storeSetting', 'delivery', 'gg', 'timeArr', 'realtimeState', 'fullSub', 'deliverySub','newSub','inStoreSetting','queueingSetting','storeWifiSetting']);
        return $this->success($model);
    }

    //商家列表
    public function storeList(Request $request)
    {
        $storeIds  = StoreGeoService::getGeoList($this->uniacid(), $request->lat, $request->lng);

        if ($request->collect) {
            $collectList  = Collect::select(["collectId"])->where("uniacid", $this->uniacid())
                ->where("type", 'store')
                ->where('userId', $this->userId())
                ->get();
            if (empty($collectList)) {
                return $this->success([]);
            }
            $collectList = collect($collectList)->pluck('collectId')->all();
            $storeIds  = array_intersect($storeIds, $collectList);
        }
        if ($request->searchIds) {
            $ids = is_array($request->searchIds) ? $request->searchIds : explode(',', $request->searchIds);
            $storeIds  = array_intersect($storeIds, $ids);
        }
        if ($request->filtrIds) {
            $ids = is_array($request->filtrIds) ? $request->filtrIds : explode(',', $request->filtrIds);
            $storeIds  = array_diff($storeIds, $ids);
        }
        $list = Store::business()
            ->with(['collectStore', 'label'])->whereIn('id', $storeIds)
            ->when($request->regionId, function ($q) use ($request) {
                return $q->where('region', 'like', "%$request->regionId%");
            })
            ->when($request->cityId &&  $request->cityId != 'undefined' && $config['pageState'] == 2, function ($q) use ($request) {
                return $q->where('region', 'like', "%$request->cityId%");
            })
            ->when($request->keyword, function ($q) use ($request) {
                return $q->where('name', 'like', "%$request->keyword%");
            })
            ->where('isolate',0)
            ->orderByRaw("FIELD(`id`," . implode(',', $storeIds) . ")")
            ->paginate($request->size ?? 10, '*', 'page');
        return $this->success(new StoreList($list));
    }
}
