<?php

namespace App\Http\Controllers\Channel\Delivery;

use App\Http\Controllers\Channel\ApiController;
use App\Models\Admin\Apply;
use App\Models\Delivery\Rule;
use App\Models\Delivery\Channel;
use App\Models\Wechat\Maiyatian\Application;
use App\Services\Delivery\QulaidaService;
use App\ServicesWaiSongBangController\Delivery\MaiyatianService;
use App\Services\Delivery\WaisongBangService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class QldController extends ApiController
{


    public function  store(Request $request)
    {

        $apply =  Apply::find($this->uniacid());
        if (!$apply) {
            return $this->failed('数据不存在');
        }

       // $key = "applyqldId" . $this->uniacid() . $this->storeId();
        //$list = Cache::get($key, false);
        //if (empty($list)) {

       $data = QulaidaService::createRelateMerchant($this->storeId());

            //cache::set($key, $data);
       // }

        return $this->success($data);
    }


}
