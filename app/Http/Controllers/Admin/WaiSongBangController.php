<?php

namespace App\Http\Controllers\Admin;

use App\Http\Resources\Admin\Sms\SmsPayCollection;
use App\Models\Collect;
use App\Models\Config;
use App\Models\SmsOrder;
use App\Services\ConfigService;
use App\Services\Delivery\WaisongBangService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WaiSongBangController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $config = ConfigService::getSystemSet('deliverySetting');
        $sys = getSysInfo();
        $data['name'] = $request->name ?? $sys['domain_name'];
        $data['mobile'] =  $request->mobile ?? $sys['phone'];
        $data['third_partner_id'] = $sys['id'];
        $data['store_independent_recharge'] = 1;
        $data['callback_url'] = Request()->getSchemeAndHttpHost() . "/channel/notify/waisongbang";
        if (!Cache::has('wsb')) {
            WaisongBangService::createPartner($data);
        } else {
            WaisongBangService::createPartner($data, true);
        }
        Cache::set('wsb', $data);
        $config = collect($config)->toArray();
        $config['name'] = $data['name'];
        $config['mobile'] = $data['mobile'];
        $config['third_partner_id'] = $data['third_partner_id'];
        $config['callback_url'] = $data['callback_url'];
        Config::where('ident', 'deliverySetting')->update(['data' => json_encode($config, 320)]);
        return $this->success(Cache::get('wsb'));
    }
}
