<?php

namespace App\Http\Controllers\ChannelApi;

use App\Models\MessageConfig;
use App\Models\OrderBill;
use App\Models\ShopBill;
use Illuminate\Http\Request;
use App\Services\LengthAwarePaginatorService;
use Illuminate\Support\Facades\Request as FacadesRequest;

class MessageConfigController extends ApiController
{
    public function index(Request $request)
    {
        $uniacid = $this->uniacid();
        $list = MessageConfig::where("sendType", $this->channel())->with(['msg' => function ($q) use ($uniacid) {
            return $q->where('uniacid', $uniacid);
        }])->whereHas('msg', function ($q) use ($uniacid) {
            return $q->where('uniacid', $uniacid)->where("state", 1)->whereNotNull('tempId');
        })->get();
        $list = collect($list)->map(function ($item) {
            return ['type' => $item->type, 'tempId' => $item->msg->tempId];
        });
        return $this->success($list);
    }
}
