<?php

namespace App\Http\Controllers\ChannelApi\InStore;

use App\Events\StoreMessageEvent;
use App\Http\Controllers\ChannelApi\ApiController;
use App\Http\Requests\Admin\ChangePassword;
use App\Http\Resources\ChannelApi\User\Profix;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\InStore\Cart;
use App\Models\Member;
use App\Models\Member\Address;
use App\Models\MemberAddress;
use App\Models\MemberBind;
use App\Models\Tables\Table;
use App\Models\UserAccount;
use App\Services\ConfigService;
use App\Services\MenuService;
use App\Services\OrderService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\StorePartner;

class TableController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        try {
            $uniacid = $this->uniacid();
            $model = Table::with([
                'store' => function ($q) use ($uniacid) {
                    return $q->select(["id", 'name', 'lat', 'lng']);
                },
                'type' => function ($q) use ($uniacid) {
                    return $q->where('uniacid', $uniacid);
                },
                'area' => function ($q) use ($uniacid) {
                    return $q->where('uniacid', $uniacid);
                }
            ])->where("uniacid", $this->uniacid())->find($id);
            if (empty($model)) {
                return $this->failed('数据不存在');
            }
            $model->setAppends(['diningType']);
            if (empty($model->store->inStoreSetting) ||$model->store->inStoreSetting['pickupSwitch'] == 0|| empty($model->store->inStoreSetting['orderMode'])) {
                return $this->failed('堂食业务已关闭');
            }
            if($model->store->inStoreSetting){
                $model->select_numbe_switch=$model->store->inStoreSetting['order']['select_numbe_switch'];
            }
            //绑定门店分销关系
            $config = ConfigService::getChannelConfig('distributor', $uniacid,0);
            if($config['storeDistribution']==1){
                $storeId=$model['store']->id;
                $userId = $this->userId();
                $row=StorePartner::where('uniacid',$this->uniacid())->where('storeId',$model['store']->id)->first();
                if(empty($row)){
                    $storePartner=new StorePartner();
                    $storePartner->uniacid=$this->uniacid();
                    $storePartner->storeId=$model['store']->id;
                    $storePartner->state=1;
                    $storePartner->save();
                }
                OrderService::partnerBind($storeId,$userId);
            }
            return $this->success($model);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }


    public function update(Request $request, $id)
    {
        $uniacid = $this->uniacid();
        $model = Table::with([
            'store' => function ($q) use ($uniacid) {
                return $q->select(["id", 'name', 'lat', 'lng']);
            },
            'type' => function ($q) use ($uniacid) {
                return $q->where('uniacid', $uniacid);
            },
            'area' => function ($q) use ($uniacid) {
                return $q->where('uniacid', $uniacid);
            }
        ])->where("uniacid", $this->uniacid())->find($id);
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        $model->state = 0;
        $model->people = $request->people ?? 0;
        // if ($this->diningType() == 4 && $model->store->inStoreSetting['order']['payMode'] == 1) {
        //     $model->expiredTime = date("Y-m-d H:i:s", intval($model->store->inStoreSetting['order']['cleanTime'] * 60) + time());
        // }
        $model->save();
        Cart::where('uniacid', $this->uniacid())
            ->where('storeId', $model->storeId)
            ->where('diningType', $this->diningType())
            ->where('tableId', 0)
            ->update(['tableId' => $id]);

        return $this->success($model);
    }
    public function callWaiter(Request $request, $id)
    {
        $model = Table::find($id);
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        Event(new StoreMessageEvent($model, 'waiter'));
        return $this->success('呼叫成功');
    }
}
