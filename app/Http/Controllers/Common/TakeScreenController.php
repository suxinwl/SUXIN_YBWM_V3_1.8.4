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
use App\Services\ConfigService;
use App\Services\ShortLinkService;
use EasyWeChat\Factory;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class TakeScreenController extends ApiController
{

    public function index(Request $request)
    {
        $id = $request->id;
        $model = ShortLink::where('shortLink', $id)->first();
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        $store = Store::where('uniacid', $model->uniacid)->find($model->storeId);
        if (empty($store)) {
            return $this->failed('门店不存在');
        }
        $id = $store->id;
        $data['makecount'] = TakeScreen::where('storeId', $id)->where('state', 3)->count();
        $data['makeing'] = TakeScreen::where('storeId', $id)->where('state', 3)->orderBy('id', 'desc')->limit(12)->get();
        $data['maked'] = TakeScreen::where('storeId', $id)->where('state', 4)->orderBy('updated_at', 'desc')->limit(12)->get();
        $data['config'] = ConfigService::getStoreConfig('takeScreen', $id);
        $data['store'] = $store->setAppends([]);
        return $this->success($data);
    }

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
        $id = $store->id;
        $data['makecount'] = TakeScreen::where('storeId', $id)->where('state', 3)->count();
        $data['makeing'] = TakeScreen::where('storeId', $id)->where('state', 3)->orderBy('id', 'desc')->limit(12)->get();
        $data['maked'] = TakeScreen::where('storeId', $id)->where('state', 4)->orderBy('updated_at', 'desc')->limit(12)->get();
        $data['config'] = ConfigService::getStoreConfig('takeScreen', $id);
        $data['store'] = $store->setAppends([]);
        return $this->success($data);
    }
}
