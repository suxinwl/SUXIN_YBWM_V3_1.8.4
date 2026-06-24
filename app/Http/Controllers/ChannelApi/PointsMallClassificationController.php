<?php
namespace App\Http\Controllers\ChannelApi;
use Illuminate\Http\Request;
use App\Models\PointsMallClassification;
class PointsMallClassificationController extends ApiController
{
    public function Index(Request $request)
    {
        $list = PointsMallClassification::where('uniacid', $this->uniacid())
            ->where('storeId',$this->isolateStore())
            ->where('display',1)
            ->orderBy('sort', 'asc')->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }
}
