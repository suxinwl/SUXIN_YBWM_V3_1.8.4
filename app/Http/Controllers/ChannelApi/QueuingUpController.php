<?php

namespace App\Http\Controllers\ChannelApi;

use App\Http\Controllers\Controller;
use App\Http\Requests\SocketMessageRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Jobs\SynUser;
use App\Models\Order\TakeScreen;
use App\Models\QueuingUp;
use App\Models\ShortLink;
use App\Models\Store;
use App\Models\Tables\Type;
use App\Services\ConfigService;
use App\Services\InStoreOrderService;
use App\Services\ShortLinkService;
use EasyWeChat\Factory;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class QueuingUpController extends ApiController
{
    public function index(Request $request)
    {
        $model = QueuingUp::with(['type', 'store'])
            ->withTrashed()
            ->where('uniacid', $this->uniacid())
            ->where('userId', $this->userId())
            ->orderBy('id', 'desc')
            ->paginate($request->size ?? 10, '*', 'page');
        return $this->success($model);
    }


    public function show(Request $request, $id)
    {
        $model = QueuingUp::with(['type', 'store'])
            ->withTrashed()
            ->where('uniacid', $this->uniacid())
            ->find($id);
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        return $this->success($model);
    }


    public function table(Request $request)
    {
        $list = Type::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->where('queueSwitch', 1)
            ->get();
        $list = collect($list)->map(function ($item) {
            $item->setAppends(['queuingUp']);
            return $item;
        });
        return $this->success($list);
    }

    public function store(Request $request)
    {
        try {
            $uniacid = $this->uniacid();
            $storeId = $this->storeId();
            if (empty($request->type_id)) {
                throw new BadRequestException('请选择桌台区域');
            }
            $row = Type::where('uniacid', $this->uniacid())->find($request->type_id);
            if ($request->people > $row->maxNum) {
                throw new BadRequestException('超出最大选择人数');
            }
            if ($request->people < $row->minNum) {
                throw new BadRequestException('不能少于最少人数');
            }
            $model = new QueuingUp();
            $model->fill($request->all());
            $model->storeId = $this->storeId();
            $model->score = $this->appType();
            $model->userId = $this->userId();
            $model->uniacid = $this->uniacid();
            $model->number = $model->getSerialNum();
            $model->serialNum = $row->serialNum . $model->number;
            $model->save();
            //$config = ConfigService::getStoreConfig('queuing', $model->storeId);
            //if (in_array($model->score, $config['channelList'] ?? [])) {
                InStoreOrderService::print($model->id, 12);
           // }
            return $this->success($model->id, '取号成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $model = QueuingUp::where('uniacid', $this->uniacid())->where('userId', $this->userId())
                ->find($id);
            if (empty($model)) {
                return $this->failed('数据不存在');
            }
            $model->update(['state' => 2, 'deleted_at' => Carbon::now()->toDateTimeString()]);
            return $this->success([], '取消成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }
}
