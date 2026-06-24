<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Http\Requests\SocketMessageRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Jobs\SynUser;
use App\Models\Order\TakeScreen;
use App\Models\ShortLink;
use App\Models\Store;
use App\Models\Tables\Type;
use App\Services\ConfigService;
use App\Services\ShortLinkService;
use DB;
use EasyWeChat\Factory;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class QueuingUpController extends ApiController
{
    public function show(Request $request, $id)
    {
        $model = ShortLink::where('shortLink', $id)->first();
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        $store = Store::where('uniacid', $model->uniacid)->find($model->storeId);
        if (empty($store)) {
            return $this->failed('门店不存在');
        }
        $list = Type::where('uniacid', $store->uniacid)
            ->where('storeId', $store->id)
            ->where('queueSwitch', 1)
            ->get();
        $list = collect($list)->map(function ($item) {
            $item->setAppends(['queuingUp']);
            return $item;
        });
        $jiaohao =  DB::table('queuing_up')
            ->where('uniacid', $store->uniacid)
            ->whereNull('deleted_at')
            ->where('storeId', $store->id)
            ->whereIn('state', [2, 3, 4])
            ->orderBy('updated_at', 'desc')
            ->first();
        $data =  [
            'jiaohao' => $jiaohao ? $jiaohao->serialNum : null,
            'list' => $list,
            'config' => ConfigService::getChannelConfig('queuing_background_image', $store->uniacid),
            'storeConfig' => ConfigService::getStoreConfig('queuing', $store->id)
        ];
        return $this->success($data);
    }
}
