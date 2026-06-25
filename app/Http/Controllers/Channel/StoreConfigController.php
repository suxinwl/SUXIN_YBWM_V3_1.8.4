<?php

namespace App\Http\Controllers\Channel;

use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Channel\ConfigRequest;
use App\Models\Kuaishou;
use App\Models\StoreConfig;
use App\Services\ConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Douyin;
use App\Models\TiktokStoreList;
use App\Models\Store;
class StoreConfigController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $ident = $request->ident;
        $data = ConfigService::getStoreConfig($ident, $this->storeId());
        return  $this->success($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ConfigRequest $request, StoreConfig $model)
    {
        $unique = StoreConfig::where(['ident' => $request->ident, 'storeId' => $this->storeId()])->first();
        if ($unique) {
            return $this->failed(__("base.unique"), 422);
        }
        $model->create([
            'storeId' => $this->storeId(),
            'ident' => $request->ident,
            'name' => $request->identName,
            'data' => $request->all(),
        ]);
        if($request->ident == 'storeSetting'){
            $this->syncStoreSwitches($request);
        }
        $key =  "storeConfigMap:" . $this->storeId();
        Cache::delete($key);
        return $this->success([], __('base.success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit()
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(ConfigRequest $request, $ident)
    {
        $model = StoreConfig::where('ident', $ident)->where('storeId', $this->storeId())->first();
        if (!$model) {
            return $this->failed('数据不存在');
        }
        $params = $request->all();

        if ($ident == 'tiktokStore' && empty($model->data['titok'])) {
            $order_type = 1;
            $data = Douyin::shopQuery($this->uniacid(), $request->tiktokStoreId);
            if ($data['data']['error_code'] == 0) {
                $tikData = $data['data']['pois'][0]['poi'];
                $params['titok'] = $tikData;
                TiktokStoreList::updateOrCreate(
                    [
                        'uniacid' => $this->uniacid(),
                        'storeId' => $this->storeId(),
                        'order_type' => 1
                    ],
                    [
                        'uniacid' => $this->uniacid(),
                        'storeId' => $this->storeId(),
                        'poi_id' => $tikData['poi_id'],
                        'poi_name' => $tikData['poi_name'],
                        'address' => $tikData['address'],
                        'latitude' => $tikData['latitude'],
                        'longitude' => $tikData['longitude'],
                        'order_type' => 1
                    ]
                );
            } else {
                return $this->failed($data['data']['description']);
            }
        }
        if ($ident == 'kuaishou_open_platforms') {
            $order_type = 2;
            $data = Kuaishou::shopQuery($request->tiktokStoreId);
            if ($data['data']['pois']) {
                $tikData = $data['data']['pois'][0]['poi'];
                $params['titok'] = $tikData;
                TiktokStoreList::updateOrCreate(
                    [
                        'uniacid' => $this->uniacid(),
                        'storeId' => $this->storeId(),
                        'order_type' => 2
                    ],
                    [
                        'uniacid' => $this->uniacid(),
                        'storeId' => $this->storeId(),
                        'poi_id' => $tikData['poi_id'],
                        'poi_name' => $tikData['poi_name'],
                        'address' => $tikData['address'],
                        'latitude' => $tikData['latitude'] ?: $tikData['latitude'],
                        'longitude' => $tikData['longitude'] ?: $tikData['longtitude'],
                        'order_type' => 2
                    ]
                );
            } else {
                return $this->failed('请填写正确的门店ID');
            }
        }
        $model->data = $params;
        $model->save();
        if($ident == 'storeSetting'){
            $this->syncStoreSwitches($request);
        }
        $key =  "storeConfigMap:" . $this->storeId();
        Cache::delete($key);
        return $this->success([], __('base.success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $ident)
    {
        $model = StoreConfig::where('ident', $ident)->where('storeId', $this->storeId())->first();
        if ($model) {
            $model->delete();
        }
        return $this->success([], __('base.success'));
    }

    private function syncStoreSwitches(Request $request)
    {
        $storeInfo = Store::where('id', $this->storeId())->first();
        if (!$storeInfo) {
            return;
        }
        if ($request->has('differentPlacesSwitch')) {
            $storeInfo->differentPlacesSwitch = $request->differentPlacesSwitch ?: 0;
        }
        if ($request->has('expressSwitch')) {
            $storeInfo->expressSwitch = $request->expressSwitch ?: 0;
        }
        $storeInfo->save();
    }
}
