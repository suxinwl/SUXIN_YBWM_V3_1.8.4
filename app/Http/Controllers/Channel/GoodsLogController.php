<?php
namespace App\Http\Controllers\Channel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Channel\ConfigRequest;
use App\Models\ChannelConfig as Config;
use App\Models\ChannelConfig;
use App\Models\GoodsLog as Log;
use Illuminate\Http\Request;
class GoodsLogController extends ApiController
{
    public function index(Request $request)
    {
        $list = Log::with(['skus','goods','store','admin'])->select(['uniacid', 'storeId', 'type', 'adminId', 'spuId', 'skuId', 'created_at', 'updated_at'])
            ->where('uniacid', $this->uniacid())
            ->when($request->keyword, function ($q) use ($request) {
                return $q->where('title', 'like', "$request->keyword%");
            })->when($request->type, function ($q) use ($request) {
                return $q->where('type', $request->type);
            })->orderBy('id', 'desc')->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }


}
