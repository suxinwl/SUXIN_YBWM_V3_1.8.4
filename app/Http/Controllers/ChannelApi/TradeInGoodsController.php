<?php

namespace App\Http\Controllers\ChannelApi;

use App\Exports\NewSubReceiveExport;
use App\Exports\PayGiftReceiveExport;
use App\Http\Controllers\Channel\ApiController;
use App\Models\TradeIn\Activity;
use App\Models\TradeIn\Goods;
use App\Models\TradeIn\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class TradeInGoodsController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $storeId = $this->storeId();
        $uniacid = $this->uniacid();
        $list = Activity::where('uniacid', $this->uniacid())
            ->with(['stores','goods'])
            ->where('state', 1)
            ->where("startTime", "<", date("Y-m-d H:i:s", time()))->where("endTime", ">=", date("Y-m-d H:i:s", time()))
            ->where(function ($q) use ($storeId, $uniacid) {
                return $q->whereHas('stores', function ($q) use ($storeId, $uniacid) {
                    return $q->where(function ($q) use ($storeId, $uniacid) {
                        return $q->where('storeId', $storeId)->where('type', 2);
                    })->orWhere(function ($q) use ($storeId, $uniacid) {
                        return $q->where('storeId', '!=', $storeId)->where('type', 3);
                    });
                })->orWhere(function ($q) use ($uniacid) {
                    return $q->where('storeType', 1)->where('uniacid', $uniacid);
                });
            })
            ->first();
        return $this->success($list);
    }
}
