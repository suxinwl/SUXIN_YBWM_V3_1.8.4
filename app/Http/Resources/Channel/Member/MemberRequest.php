<?php

namespace App\Http\Resources\Channel\Member;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class MemberRequest extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'                    =>  $this->id,
            'nickname'              =>  $this->nickname,
            'mobile'                =>  $this->mobile,
            'realname'                =>  $this->realname,
            'sex'                =>  $this->sex,
            'sexFormat'                =>  $this->sexFormat(),
            'birthday'                =>  $this->birthday,
            'account'                =>  $this->account,
            'store'                =>  $this->store,
            'avatar'                =>  $this->avatar ?? $this->apply->applyImage,
            'scoreFormat' => $this->scoreFormat,
            'payTime' => $this->payTime,
            'score' =>  $this->score,
            'labelId' => $this->labelId,
            'group' => $this->group,
            'vip' => $this->vip,
            'store' => $this->store,
            'vipCard' => $this->vipCard,
            'vipCreateTime' => $this->vipCreateTime,
            'region' => $this->regionFormat,
            'label' => $this->label,
            'notes' => $this->notes,
            'state' => $this->state,
            'subscribe' => $this->subscribe(),
            'miniOpenid' => $this->getMiniOpenid() ?: '',
            'wechatOpenid' => $this->getWechatOpenid() ?: '',
            'unionid' => $this->getUnionid() ?: '',
            'registerStore' => $this->registerStoreData,
            'payStore'=>$this->payStore,
            'coupons'=>$this->coupons()->count(),
            'bindList'              =>  collect($this->memberBind)->pluck('type'),
            'created_at'            =>  $this->created_at->format('Y-m-d H:i:s'),
            'updated_at'            =>  $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
