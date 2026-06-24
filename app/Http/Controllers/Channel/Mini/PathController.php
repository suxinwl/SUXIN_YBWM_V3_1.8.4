<?php

namespace App\Http\Controllers\Channel\Mini;

use App\Http\Controllers\Channel\ApiController;
use App\Models\Mini\ApplyMiniPath;
use App\Models\Mini\MiniPath;
use App\Models\ShortLink;
use App\Services\OpenWechat\AdminOpenWechat;
use App\Services\OpenWechat\ChannelOpenWechat;
use App\Services\ShortLinkService;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class PathController extends ApiController
{
    public function index(Request $request)
    {
        $uniacid = $this->uniacid();
        $list  = MiniPath::with(['wx' => function ($q) use ($uniacid) {
            return $q->where('uniacid', $uniacid);
        }])
            ->orderBy('id', 'asc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function update(Request $request, $id)
    {
        if (empty($request->channel)) {
            return $this->failed('渠道不能为空');
        }
        $path = MiniPath::find($id);
        if (!$path) {
            return $this->failed('路径不存在');
        }
        $model = ApplyMiniPath::where('type', $path->type)->where('uniacid', $this->uniacid())->where('channel', $request->channel)->first();
        if ($model) {
            return $this->success('操作成功');
        }
        $model = new ApplyMiniPath(['uniacid' => $this->uniacid(), 'type' => $path->type, 'channel' => $request->channel]);
        $model->add();
        $model->save();
        return $this->success('操作成功');
    }

    public function destroy(Request $request, $id)
    {
        if (empty($request->channel)) {
            return $this->failed('渠道不能为空');
        }
        $path = MiniPath::find($id);
        if (!$path) {
            return $this->failed('路径不存在');
        }
        $model = ApplyMiniPath::where('type', $path->type)->where('uniacid', $this->uniacid())->where('channel', $request->channel)->first();
        if (!$model) {
            return $this->success('操作成功');
        }
        $model->clear();
        $model->delete();
        return $this->success('操作成功');
    }

    public function qrcode(Request $request, $id)
    {
        $path = MiniPath::find($id);
        if (!$path) {
            return $this->failed('路径不存在');
        }
        // $model = ApplyMiniPath::where('type', $path->type)->where('channel', $request->channel)->first();
        // if (empty($model)) {
        //     return $this->failed('请开启小程序关联');
        // }
        // $shortLink = ShortLink::where('uniacid', $this->uniacid())
        //     ->where('storeId', $this->storeId())
        //     ->where('type', $model->type)
        //     ->first();
        // if (empty($shortLink)) {
        //     if (in_array($request->type, ['index', 'storeGoods', 'orderIndex', 'myIndex', 'addresses', 'couponCenter', 'queuingUp'])) {
        //         $shortLink = ShortLinkService::createUniacidLink($path,$this->uniacid());
        //     }
        // }
        $url = Request()->getSchemeAndHttpHost() . "/s/{$path->type}/" . $this->uniacid() . '/';
        $url = "data:image/png;base64," . base64_encode(QrCode::format('png')->size(400)->generate($url));
        return $this->success($url);
    }
}
