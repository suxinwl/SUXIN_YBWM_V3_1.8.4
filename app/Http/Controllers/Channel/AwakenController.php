<?php

namespace App\Http\Controllers\Channel;

use App\Http\Resources\Channel\Menus\Menus;
use App\Models\Member;
use App\Models\Spec;
use App\Services\CouponService;
use App\Services\MemberAccountService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use App\Imports\SpecsImport;
use App\Models\AttrValue;
use App\Models\Goods\SpuAttrValueIds;
use App\Models\Goods\SpuSpecIds;
use App\Models\Goods\SpuSpecValueIds;
use App\Models\SpecValue;
use App\Models\MemberAccountLog;

class AwakenController extends ApiController
{

    public function store(Request $request)
    {
        switch ($request->classify){
            case 1;//按会员等级
                $user=Member::where('vipId',$request->level)->get();
            break;
            case 2;//按会员标签
                $user=Member::whereIn('labelId',$request->tags)->get();
                break;
           case 3;// 按会员分组
               $user=Member::where('groupId',$request->group)->get();
                break;
            case 4;//自定义用户
                $ids=array_column($request->checkMemberList,'id');
                $user=Member::whereIn('id',$ids)->get();
                break;
        }
        try{
            if($user){
                if($request->couponSwitch&&$request->couponGive){
                    foreach ($user as $v){
                        CouponService::issue($request->couponGive, $v->id, 23);
                    }

                }
                if($request->balanceSwitch&&$request->balance){
                    foreach ($user as $v){
                        MemberAccountService::changeBalance(intval($v->id), 1, $request->balance, MemberAccountLog::BASE, $this->user()->id, '回头智能唤醒');
                    }
                }
                if($request->integralSwitch&&$request->integral){
                    foreach ($user as $v){
                        MemberAccountService::changeIntegral(intval($v->id), 1, $request->integral, MemberAccountLog::BASE, $this->user()->id, '回头智能唤醒');
                    }
                }
            }
            return $this->success([]);
        } catch (\Exception $e) {
            file_put_contents('huitouhuanxing.log',$e->getMessage());
            return $this->failed($e->getMessage());
        }

    }


}
