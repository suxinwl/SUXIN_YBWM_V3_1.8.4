<?php

namespace App\Http\Controllers\Channel;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenusRequest;
use App\Http\Resources\Channel\Menus\Menus;
use App\Models\Menu;
use App\Models\RoleMenu;
use App\Models\Store;
use App\Models\StoreLabel;
use App\Models\VoiceMessage;
use App\Services\DataSeederService;
use Illuminate\Http\Request;
use App\Services\MenuService;
use App\Traits\HelperTrait;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class VoiceMessageController extends ApiController
{

    public function index(Request $request)
    {
        $list = VoiceMessage::where('uniacid', $this->uniacid())
            ->where('storeId',$this->isolateStore())
            ->orderBy('sort', 'asc')
            ->get();
        if (collect($list)->count() == 0) {
            DataSeederService::applyVoiceSeed($this->uniacid());
            $list = VoiceMessage::where('uniacid', $this->uniacid())
                ->orderBy('sort', 'asc')
                ->get();
        }
        return $this->success($list);
    }


    public function update(Request $request, $id)
    {
        try {
            $model = VoiceMessage::where('uniacid', $this->uniacid())
            ->where('storeId',$this->isolateStore())
            ->find($id);
            if (empty($model)) {
                throw new BadRequestException('数据不存在');
            }
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->save();
            return $this->success([], '保存成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }
}
