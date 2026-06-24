<?php

namespace App\Http\Controllers\ChannelApi;
use App\Models\Store;
use App\Services\StoreGeoService;
use Illuminate\Support\Facades\DB;
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
use Illuminate\Support\Facades\Storage;

class ConfigController extends ApiController
{

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $config = ConfigService::getChannelConfig($id, $this->uniacid());
        return $this->success($config);
    }

    public function storeConfig(Request $request, $storeId, $id)
    {
        $config = ConfigService::getStoreConfig($storeId, $id);
        return $this->success($config);
    }


    public function configFormMap(Request $request)
    {
        $uniacid = Request()->header('uniacid');
        $config = [];
        foreach ($request->idents as $key => $ident) {
            $config[$ident] = ConfigService::getChannelConfigFormMap($this->uniacid(), $ident, $this->storeId());
        }
        if($request->lat&&$request->lng){
            $lat=$request->lat;
            $lng=$request->lng;
            $storeBasicSetting = ConfigService::getChannelConfig('storeBasicSetting', $this->uniacid());
            $default  = StoreGeoService::getRadius($this->uniacid(), $request->lat, $request->lng, $storeBasicSetting['shopKm'], 'km', ["ASC", "COUNT" => 1]);
            $config['shopInfo']=Store::where('id',$default)->first();
//            $config['shopInfo']=DB::table('store')->selectRaw("id,lat,lng,name,
//  ROUND(ST_DISTANCE(point(lng,lat),point({$lng},{$lat})) /0.0111,2) distance")
//                ->where('uniacid',$this->uniacid())
//                ->whereNull('deleted_at')->orderBy('distance','asc')->orderBy('id','desc')
//                ->first();

        }
        return $this->success($config);
    }

    public function copyright(Request $request)
    {
    }
}
