<?php

namespace App\Http\Controllers\Channel;

use App\Enums\WechatEnum;
use App\Http\Resources\Channel\WechatReply\KeyListResources;
use App\Models\WechatAttachment;
use App\Models\WechatMenu;
use App\Models\WechatReply;
use App\Services\ConfigService;
use App\Services\OpenWechat\ChannelOpenWechat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class WechatReplyController extends ApiController
{
    public function key(Request $request)
    {
        $list = WechatReply::where('channel', 1)->where('uniacid', $this->uniacid())->orderBy('sort', 'asc')->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success(new KeyListResources($list));
    }

    public function index(Request $request)
    {
        $list = WechatReply::where('channel', 1)->where(function ($q) use ($request) {
            if ($request->keyword) {
                $q->where("keyword", "like", "%$request->keyword%");
            }
            return $q;
        })->where('uniacid', $this->uniacid())->orderBy('sort', 'asc')->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function focus(Request $request)
    {
        $list = WechatReply::where('uniacid', $this->uniacid())->where('channel', 2)->first();
        return $this->success($list);
    }

    public function default(Request $request)
    {
        $list = WechatReply::where('uniacid', $this->uniacid())->where('channel', 3)->first();
        return $this->success($list);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['uniacid'] = $this->uniacid();
        WechatReply::create($data);
        return $this->success([], '创建成功');
    }

    public function show(Request $request, $id)
    {
        $info = WechatReply::find($id);
        return $this->success($info);
    }

    public function update(Request $request, $id)
    {
        $model = WechatReply::where('uniacid', $this->uniacid())->where('id', $id)->first();
        if (!$model) {
            return $this->failed('数据不存在');
        }
        $model->fill($request->all());
        $model->save();
        return $this->success();
    }

    public function state(WechatReply $admin_model, $id)
    {
        $admin_model = $admin_model->find($id);
        if (empty($id)) {
            return $this->success([], __('base.nodata'));
        }
        $admin_model->state = $admin_model->state == 1 ? 0 : 1;
        $admin_model->save();
        return $this->success([], '状态调整成功');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $idArray = array_filter(explode(',', $id), function ($item) {
            return is_numeric($item);
        });
        WechatReply::destroy($idArray);
        return $this->success([], '删除成功');
    }
}
