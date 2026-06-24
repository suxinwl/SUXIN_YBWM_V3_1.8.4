<?php

namespace App\Http\Controllers\Channel\Table;

use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Admin\ChangePassword;
use App\Models\Ad;
use App\Models\InStore\Cart;
use App\Models\publicMiniProgram\PublicMiniprogramModel;
use App\Models\Tables\ServersIds;
use App\Models\Tables\Table;
use App\Services\OpenWechat\ChannelOpenWechat;
use App\Services\ShortLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Storage;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class TableController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $uniacid = $this->uniacid();
        $storeId = $this->storeId();
        $list = Table::with([
            'shortLink' => function ($q) use ($uniacid, $storeId) {
                return $q->where('uniacid', $uniacid)->where('storeId', $storeId);
            },
            'area' => function ($q) use ($uniacid, $storeId) {
                return $q->where('uniacid', $uniacid)->where('storeId', $storeId);
            },
            'type' => function ($q) use ($uniacid, $storeId) {
                return $q->where('uniacid', $uniacid)->where('storeId', $storeId);
            },
        ])->where('uniacid', $this->uniacid())
            ->when($request->name, function ($q) use ($request) {
                return $q->where("name", "like", "%{$request->name}%");
            })->when($request->areaId, function ($q) use ($request) {
                return $q->where("areaId", $request->areaId);
            })
            ->when($request->typeId, function ($q) use ($request) {
                return $q->where("areaId", $request->typeId);
            })->when($request->filterServer, function ($q) use ($request, $uniacid, $storeId) {
                $ids = ServersIds::where("uniacid", $uniacid)->where('storeId', $storeId)->where('serverId', "!=", $request->serverId ?? 0)->get();
                $ids = collect($ids)->pluck('tableId')->all();
                if ($ids) {
                    return $q->whereNotIn('id', $ids);
                }
                return $q;
            })
            ->where('storeId', $storeId)
            ->orderByWith('area', 'sort', 'asc')
            ->orderByWith('type', 'sort', 'asc')
            ->paginate(Request()->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function store(Request $request)
    {
        try {
            $model = new Table();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->save();
            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }


    public function batch(Request $request)
    {
        try {
            for ($i = 0; $i <= $request->num - 1; $i++) {
                $name = $request->fix . ($request->start + $i);
                Table::create([
                    'uniacid' => $this->uniacid(),
                    'storeId' => $this->storeId(),
                    'typeId' => $request->typeId,
                    'areaId' => $request->areaId,
                    'name' => $name
                ]);
            }
            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $uniacid = $this->uniacid();
            $storeId = $this->storeId();
            $model = Table::with([
                'shortLink' => function ($q) use ($uniacid, $storeId) {
                    return $q->where('uniacid', $uniacid)->where('storeId', $storeId);
                },
                'area' => function ($q) use ($uniacid, $storeId) {
                    return $q->where('uniacid', $uniacid)->where('storeId', $storeId);
                },
                'type' => function ($q) use ($uniacid, $storeId) {
                    return $q->where('uniacid', $uniacid)->where('storeId', $storeId);
                },
            ])->where("uniacid", $this->uniacid())->where('storeId', $storeId)->find($id);
            if (empty($model)) {
                return $this->failed('数据不存在');
            }
            return $this->success($model);
        } catch (\Exception $e) {
            return $this->failed('失败');
        }
    }

    public function qrcode(Request $request, $id)
    {
        try {
            $uniacid = $this->uniacid();
            $storeId = $this->storeId();

            $model = Table::with([
                'shortLink' => function ($q) use ($uniacid, $storeId) {
                    return $q->where('uniacid', $uniacid)->where('storeId', $storeId);
                },
                'area' => function ($q) use ($uniacid, $storeId) {
                    return $q->where('uniacid', $uniacid)->where('storeId', $storeId);
                },
                'type' => function ($q) use ($uniacid, $storeId) {
                    return $q->where('uniacid', $uniacid)->where('storeId', $storeId);
                },
            ])->where("uniacid", $uniacid)->where('storeId', $storeId)->find($id);
            if (empty($model)) {
                return $this->failed('数据不存在');
            }
            $name = $model->area->name . '-' . $model->type->name . "-" . $model->name . '.png';


            if($request->get('type')=='program'){
                $path = '/' . $uniacid . '/programTableQr/' . $storeId . '/';
                if (!Storage::disk('public')->exists($path . $name)) {
                    $page='pages/shop/in/goods?tableId='.$id.'&storeId='.$storeId;
                    $app = ChannelOpenWechat::miniProgram($uniacid);
                    $response = $app->app_code->get($page);
                    $image = $response->getBody()->getContents();
                    Storage::disk('public')->put($path . $name, $image);
                }
                $imageContent = Storage::disk('public')->get($path . $name);
                $base64ImageContent = base64_encode($imageContent);
                $url='data:image/jpeg;base64,' . $base64ImageContent;
            }else{
                if (empty($model->shortLink)) {
                    $link = ShortLinkService::createTableLink($model);
                } else {
                    $link = $model->shortLink;
                }
                $path = '/' . $uniacid . '/tableQr/' . $storeId . '/';
                $url = Request()->getSchemeAndHttpHost() . '/s/table/' . $uniacid . '/'  . $link->shortLink . "?tableId=$id&storeId={$storeId}&isolate=" . $model->store->isolate;
                if (!Storage::disk('public')->exists($path . $name)) {
                    Storage::disk('public')->put($path . $name, QrCode::format('png')->size(400)->generate($url));
                }
                $url= "data:image/png;base64," . base64_encode(QrCode::format('png')->size(400)->generate($url));
            }
            if ($request->download) {
                return Storage::disk('public')->download($path . '/' . $name, urlencode($name), ['filename' => urlencode($name)]);
            }
            return $this->success($url);
        } catch (\Exception $e) {
            return $this->failed('失败');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $model = Table::where("uniacid", $this->uniacid())->find($id);
            if (empty($model)) {
                return $this->failed('数据不存在');
            }
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->save();
            return $this->success([], '修改成功');
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
            $models = Table::where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
            foreach ($models as $key => $model) {
                $model->shortLink()->delete();
                $model->delete();
            }
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }

    public function batchDownload(Request $request)
    {
        $list = Table::where('uniacid', $this->uniacid())->where('storeId', $this->storeId())->get();
        $zipArr = [];
        $storeName = '';
        if($request->type=='program'){
            $path = '/' . $this->uniacid() . '/programTableQr/' . $this->storeId() . '/';
            collect($list)->each(function ($table, $key) use ($path, &$zipArr, &$storeName) {
                $storeName = $table->store->name;
                $name = $table->area->name . '-' . $table->type->name . "-" . $table->name . '.png';
                if (!Storage::disk('public')->exists($path . $name)) {
                    $page='pages/shop/in/goods?tableId='.$table->id.'&storeId='.$table->storeId;
                    $app = ChannelOpenWechat::miniProgram($table->uniacid);
                    $response = $app->app_code->get($page);
                    $image = $response->getBody()->getContents();
                    Storage::disk('public')->put($path . $name, $image);
                }
                $zipArr[$key] = [
                    'file' => storage_path('app/public' . $path . $name),
                    'name' => $name
                ];
            });
        }else{
            $path = '/' . $this->uniacid() . '/tableQr/' . $this->storeId() . '/';
            if(!file_exists($path)){
                mkdir($path);
                chmod($path,0777);
            }
//            if (File::isDirectory(storage_path('/app/public' . '/' . $this->uniacid() . '/tableQr/' . $this->storeId()))) {
//                File::deleteDirectory(storage_path('/app/public' . '/' . $this->uniacid() . '/tableQr/' . $this->storeId()));
//            }
//            Storage::disk('public')->delete($path . '/*.png');
            collect($list)->each(function ($table, $key) use ($path, &$zipArr, &$storeName) {
                if (empty($table->shortLink)) {
                    $link = ShortLinkService::createTableLink($table);
                } else {
                    $link = $table->shortLink;
                }
                $storeName = $table->store->name;
                $name = $table->area->name . '-' . $table->type->name . "-" . $table->name . '.png';
                if (!Storage::disk('public')->exists($path . $name)) {
                    $url = Request()->getSchemeAndHttpHost() . '/s/table/' . $this->uniacid() . '/'  . $link->shortLink . "?tableId={$table->id}&storeId={$table->storeId}&isolate=" . $table->store->isolate;
                    Storage::disk('public')->put($path . $name, QrCode::format('png')->size(400)->generate($url));
                }
                $zipArr[$key] = [
                    'file' => storage_path('app/public' . $path . $name),
                    'name' => $name
                ];
            });
        }
        if (!empty($zipArr)) {
            $zip_file = storage_path('app/public' . $path . '桌码.zip');
            $zip = new \ZipArchive();
            $zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            foreach ($zipArr as $key => $file) {
                $zip->addFile($file['file'], basename($file['name']));
            }
            $zip->close();
            $fileName = $storeName . '桌码' . '.zip';
            $fileName =  urlencode(iconv('utf-8', 'utf-8', $fileName));
            return Storage::disk('public')->download($path . '桌码.zip', $fileName, ['filename' => $fileName]);
        }
        return $this->success([]);
    }


    public function clear(Request $request, $id)
    {
        $model = Table::where("uniacid", $this->uniacid())->find($id);
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        $model->orderSn = null;
        $model->state = 0;
        $model->people = 0;
        $model->save();
        Cart::where('uniacid', $model->uniacid)
            ->where('storeId', $model->storeId)
            ->where('tableId', $model->id)
            ->where('discountType', 4)
            ->delete();
        return $this->success('操作成功');
    }
}
