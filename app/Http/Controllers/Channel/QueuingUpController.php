<?php

namespace App\Http\Controllers\Channel;

use App\Events\StoreMessageEvent;
use App\Http\Resources\Channel\QueuingUp\ListResources;
use App\Services\ConfigService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Models\QueuingUp;
use App\Models\ShortLink;
use App\Models\Store;
use App\Models\Tables\Type;
use App\Services\InStoreOrderService;
use App\Services\ShortLinkService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class QueuingUpController extends ApiController
{
    public function statistics(Request $request)
    {
        $storeId = $this->storeId();
        $user = $this->user();
        $list = QueuingUp::with(['store',])->where('uniacid', $this->uniacid())
            ->when($request->keyword, function ($q) use ($request) {
                return $q->where('serialNum', 'like', "%$request->keyword%");
            })
            ->when($request->startTime, function ($q) use ($request) {
                return $q->where('created_at', '>=', $request->startTime);
            })
            ->when($request->endTime, function ($q) use ($request) {
                return $q->where('created_at', '<=', $request->endTime);
            })
            ->when($user, function ($q) use ($user) {
                if ($user->isAdmin == 0) {
                    if (empty($user->storeId)) {
                        $q->where('storeId', 0);
                    } else {
                        $q->whereIn('storeId', $user->storeId);
                    }
                }
                return $q;
            })
            ->when($storeId, function ($q) use ($storeId) {
                return $q->where('storeId', $storeId);
            })
            ->groupBy('day', 'storeId')
            ->orderBy('id', 'desc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success(new ListResources($list));
    }

    public function view(Request $request, $id)
    {
        $model = QueuingUp::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        $list = QueuingUp::with(['type', 'store'])->withTrashed()->where('uniacid', $model->uniacid)
            ->when($request->keyword, function ($q) use ($request) {
                return $q->where('serialNum', 'like', "%$request->keyword%");
            })
            ->where('day', $model->day)
            ->where('storeId', $model->storeId)
            ->orderBy('id', 'desc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function index(Request $request)
    {
        $storeId = $this->storeId();
        $user = $this->user();
        $list = QueuingUp::with(['store', 'type'])
            ->where('uniacid', $this->uniacid())
            ->where('state', 1)
            ->when($request->type_id, function ($q) use ($request) {
                return $q->where('type_id', $request->type_id);
            })
            ->when($request->keyword, function ($q) use ($request) {
                return $q->where('serialNum', 'like', "%$request->keyword%");
            })
            ->when($request->keyword, function ($q) use ($request) {
                return $q->where('serialNum', 'like', "%$request->keyword%");
            })
            ->when($user, function ($q) use ($user) {
                if ($user->isAdmin == 0) {
                    if (empty($user->storeId)) {
                        $q->where('storeId', 0);
                    } else {
                        $q->whereIn('storeId', $user->storeId);
                    }
                }
                return $q;
            })
            ->when($storeId, function ($q) use ($storeId) {
                return $q->where('storeId', $storeId);
            })
            ->orderBy('created_at', 'asc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success(new ListResources($list));
    }

    public function show(Request $request, $id)
    {
        $model = QueuingUp::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        return $this->success($model);
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
            $model->uniacid = $this->uniacid();
            $model->number = $model->getSerialNum();
            $model->serialNum = $row->serialNum .  $model->number;
            $model->save();
            $config = ConfigService::getStoreConfig('queuing', $model->storeId);
            if (in_array($model->score, $config['queueingSetting'] ?? [])) {
                InStoreOrderService::print($model->id, 12);
            }
            return $this->success($model->id, '取号成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $model = QueuingUp::where('uniacid', $this->uniacid())->find($id);
            if (empty($model)) {
                throw new BadRequestException('数据不存在');
            }
            $model->state = $request->state ?? $model->state;
            $model->save();
            return $this->success([], '保存成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public function call(Request $request, $id)
    {
        try {
            $model = QueuingUp::where('uniacid', $this->uniacid())->find($id);
            if (empty($model)) {
                throw new BadRequestException('数据不存在');
            }
            $model->save();
            event(new StoreMessageEvent($model, 'queuing'));
            return $this->success([], '叫号成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }
    public function destroy(Request $request, $id)
    {
        try {
            $idArray = array_filter(explode(',', $id), function ($item) {
                return is_numeric($item);
            });
            $models = QueuingUp::where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public function clear(Request $request)
    {
        QueuingUp::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->update(['state' => 2, 'deleted_at' => Carbon::now()->toDateTimeString()]);
        $tag = "serialNum:store:{$this->storeId()}";
        Cache::tags($tag)->flush();
        return $this->success([], '清除成功');
    }
    // GET /create 创建页展示
    public function url(Request $request, $id)
    {
        $store = Store::where('uniacid', $this->uniacid())->find($id);
        if (empty($store)) {
            throw  new BadRequestException('数据不存在');
        }
        $model = ShortLink::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->where('type', 'queuingUp')
            ->first();
        if (empty($model)) {
            $model = ShortLinkService::takeScreen($store);
        }
        $url = Request()->getSchemeAndHttpHost() . "/admin/#/workbench/takeMeal?type=1&id=" . $model->shortLink;
        return $this->success($url);
    }
}
