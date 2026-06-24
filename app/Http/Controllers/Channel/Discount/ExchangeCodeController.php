<?php

namespace App\Http\Controllers\Channel\Discount;

use App\Http\Controllers\Channel\ApiController;
use App\Http\Resources\Channel\ExchangeCode\ExchangeCodeResources;
use App\Models\ExchangeCode\ExchangeCode;
use App\Models\ExchangeCode\ExchangeCodeReceive;
use App\Models\ShortLink;
use App\Services\ShortLinkService;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Illuminate\Support\Facades\File;
use Storage;
class ExchangeCodeController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = ExchangeCode::with(['code'])->where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->when($request->type, function ($q) use ($request) {
                return $q->where('type', $request->type);
            })->when($request->subState, function ($q) use ($request) {
                if ($request->subState == "begin") {
                    return $q->where("startTime", ">", date("Y-m-d H:i:s", time()));
                }
                if ($request->subState == "start") {
                    return $q->where("startTime", "<", date("Y-m-d H:i:s", time()))->where("endTime", ">=", date("Y-m-d H:i:s", time()));
                }
                if ($request->subState == "end") {
                    return $q->where("endTime", "<", date("Y-m-d H:i:s", time()));
                }
            })
            ->when($request->name, function ($q) use ($request) {
                return $q->where("name", "like", "%{$request->name}%");
            })->when($request->startTime, function ($q) use ($request) {
                return $q->where("created_at", "<=", $request->startTime);
            })
            ->when($request->endTime, function ($q) use ($request) {
                return $q->where("created_at", ">=", $request->endTime);
            })
            ->orderBy('id', 'desc')
            ->paginate(Request()->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function store(Request $request)
    {
        try {
            $model = new ExchangeCode();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->isolateStore();
            $model->save();
            if ($model->type == 2) {
                $syncData[] = [
                    'uniacid' => $model->uniacid,
                    'userId' => 0,
                    'exchangeCodeId' => $model->id,
                    'type' => $model->type,
                    'sn' => CouponRandInt(10),
                    'state' => 1,
                    'display' => 0,
                    'storeId' => $model->storeId,
                    'created_at' => date("Y-m-d H:i:s", time()),
                    'updated_at' => null,
                ];
            } else {
                for ($i = 0; $i < $model->num; $i++) {
                    $syncData[] = [
                        'uniacid' => $model->uniacid,
                        'userId' => 0,
                        'exchangeCodeId' => $model->id,
                        'type' => $model->type,
                        'sn' => CouponRandInt(10),
                        'state' => 1,
                        'display' => 1,
                        'storeId' => $model->storeId,
                        'created_at' => date("Y-m-d H:i:s", time()),
                        'updated_at' => null,
                    ];
                }
            }
            ExchangeCodeReceive::insert($syncData);
            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }


    public function show(Request $request, $id)
    {
        try {
            $model = ExchangeCode::where("uniacid", $this->uniacid())->find($id);
            if (empty($model)) {
                return $this->failed('数据不存在');
            }
            return $this->success($model);
        } catch (\Exception $e) {
            return $this->failed('失败');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $model = ExchangeCode::where("uniacid", $this->uniacid())->find($id);
            if (empty($model)) {
                return $this->failed('数据不存在');
            }
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            if ($model->getOriginal('num') > $model->num) {
                return $this->failed('追加券数量必须大于' . $model->getOriginal('num'));
            }
            $model->save();
            if ($model->type == 1 && $model->num - $model->getOriginal('num') > 0) {
                for ($i = 0; $i <  $model->num - $model->getOriginal('num'); $i++) {
                    $syncData[] = [
                        'uniacid' => $model->uniacid,
                        'userId' => 0,
                        'exchangeCodeId' => $model->id,
                        'type' => $model->type,
                        'sn' => CouponRandInt(10),
                        'state' => 1,
                        'display' => 1,
                        'storeId' => $model->storeId,
                        'created_at' => date("Y-m-d H:i:s", time()),
                        'updated_at' => date("Y-m-d H:i:s", time()),
                    ];
                }
                ExchangeCodeReceive::insert($syncData);
            }
            return $this->success([], '修改成功');
        } catch (\Exception $e) {
            return $this->failed('修改失败');
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $idArray = array_filter(explode(',', $id), function ($item) {
                return is_numeric($item);
            });
            $models = ExchangeCode::where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
            foreach ($models as $key => $model) {
                $model->receives()->where('state', 1)->delete();
                $model->delete();
            }
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }

    /**
     * 拉黑/洗白
     */
    public function state(Request $request, $id)
    {
        $model = ExchangeCode::where('id', $id)->first();
        if (!$model) {
            return $this->failed(__('base.status_error'));
        }
        $model->state = $model->state == 1 ? 0 : 0;
        $model->save();
        $model->receives()->where('state', 1)->update(['state', 0]);
        return $this->success([]);
    }

    public function receive(Request $request)
    {
        $list = ExchangeCodeReceive::with(['member', 'exchangeCode'])
            ->where('storeId', $this->storeId())
            ->where('uniacid', $this->uniacid())
            ->where('display', 1)
            ->when($request->type, function ($q) use ($request) {
                return $q->where('type', $request->type);
            })
            ->when($request->state, function ($q) use ($request) {
                if ($request->state == 'normal') {
                    return $q->where('state', 1);
                }
                if ($request->state == 'issue') {
                    return $q->where('state', 2);
                }
                if ($request->state == 'invalid') {
                    return $q->where('state', 0);
                }
            })
            ->when($request->exchangeCodeId, function ($q) use ($request) {
                return $q->where('exchangeCodeId', $request->exchangeCodeId);
            })
            ->when($request->mobile, function ($q) use ($request) {
                return $q->whereHas("mobile", $request->mobile);
            })->when($request->startTime, function ($q) use ($request) {
                return $q->where("created_at", "<=", $request->startTime);
            })
            ->when($request->endTime, function ($q) use ($request) {
                return $q->where("created_at", ">=", $request->endTime);
            })
            ->orderBy('state', 'desc')
            ->orderBy('updated_at', 'desc')
            ->paginate(Request()->pageSize ?? 20, '*', 'pageNo');
        return $this->success(new ExchangeCodeResources($list));
    }

    public function qrCode(Request $request, $id)
    {
        $model = ExchangeCodeReceive::with(['member'])
            ->where('storeId', $this->storeId())
            ->where('uniacid', $this->uniacid())
            ->where('id', $id)
            ->first();
        if (empty($model)) {
            return $this->failed('兑换码不存在');
        }
        $link = ShortLink::where('uniacid', $this->uniacid())
            ->where('type', 'exChange')
            ->where('ident', $model->sn)
            ->first();
        if (empty($shortLink)) {
            $link = ShortLinkService::createExchange($model);
        }
        $url = Request()->getSchemeAndHttpHost() . '/s/exchange/' . $this->uniacid() . '/'  . $link->shortLink . "?code={$model->sn}&storeId={$model->storeId}&isolate=" . $this->isolate();
        $img =  QrCode::format('png')->size(400)->generate($url);    //format 是指定生成文件格式  默认格式是svg,可以直接在浏览器打开，png不能直接显示
        $code_url = 'data:image/png;base64,' . base64_encode($img);
        return $this->success($code_url);
    }

    public function receiveDelete(Request $request, $id)
    {
        $idArray = array_filter(explode(',', $id), function ($item) {
            return is_numeric($item);
        });
        ExchangeCodeReceive::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->whereIn('id', $idArray)->where('state', 1)
            ->update(['state' => 0]);
        return $this->success(null, '操作成功');
    }

    public function batchExchangeCode(Request $request)
    {
        $list = ExchangeCodeReceive::with(['member', 'exchangeCode'])
            ->where('storeId', $this->storeId())
            ->where('uniacid', $this->uniacid())
            ->where('display', 1)
            ->where('state', 1)
            ->where('userId', 0)
            ->where('type', 1)
            ->when($request->exchangeCodeId, function ($q) use ($request) {
                return $q->where('exchangeCodeId', $request->exchangeCodeId);
            })
            ->get();

        $zipArr = [];
        $storeName = '';

        $path = '/' . $this->uniacid() . '/exchangeCode/' . $this->storeId() . '/';
        // if (File::isDirectory(storage_path('/app/public' . '/' . $this->uniacid() . '/exchangeCode/' . $this->storeId()))) {
        //     File::deleteDirectory(storage_path('/app/public' . '/' . $this->uniacid() . '/exchangeCode/' . $this->storeId()));
        // }
        // Storage::disk('public')->delete($path . '/*.png');
        collect($list)->each(function ($model, $key) use ($path, &$zipArr, &$storeName) {
            $re = ShortLink::where('uniacid', $this->uniacid())
                ->where('type', 'exChange')
                ->where('ident', $model->sn)
                ->first();

            if (empty($re->shortLink)) {
                $link = ShortLinkService::createExchange($model);
            }
            $name = $model->sn . '.png';

            if (!Storage::disk('public')->exists($path . $name)) {
                $url = Request()->getSchemeAndHttpHost() . '/s/exchange/' . $this->uniacid() . '/'  . $link->shortLink . "?code={$model->sn}&storeId={$model->storeId}&isolate=" . $this->isolate();
                Storage::disk('public')->put($path . $name, QrCode::format('png')->size(400)->generate($url));
            }
            $zipArr[$key] = [
                'file' => storage_path('app/public' . $path . $name),
                'name' => $name
            ];
        });

        if (!empty($zipArr)) {
            $zip_file = storage_path('app/public' . $path . '兑换码.zip');
            $zip = new \ZipArchive();
            $zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            foreach ($zipArr as $key => $file) {
                $zip->addFile($file['file'], $file['name']);
            }
            $zip->close();
            $fileName = $storeName . '桌码' . '.zip';
            $fileName =  urlencode(iconv('utf-8', 'utf-8', $fileName));
            return Storage::disk('public')->download($path . '兑换码.zip', $fileName, ['filename' => $fileName]);
        }
        return $this->success([]);
    }


}
