<?php

namespace App\Http\Resources\Channel\Member;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;

class MemberList extends ResourceCollection
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
            'list' => $this->collection->map(function ($item) {
                return [
                    'id'                    =>  $item->id,
                    'nickname'              =>  $item->nickname,
                    'mobile'                =>  $item->mobile,
                    'realname'                =>  $item->realname,
                    'sex'                =>  $item->sex,
                    'birthday'                =>  $item->birthday,
                    'account'                =>  $item->account,
                    'couponList'                =>  $item->coupons,
                    'coupons_count'=>$item->coupons_count,
                    'avatar'                =>  $item->avatar ?? $item->apply->applyImage,
                    'sexFormat' => $item->sexFormat(),
                    'scoreFormat' => $item->scoreFormat,
                    'score' =>  $item->score,
                    'labelId' => $item->labelId,
                    'group' => $item->group,
                    'vip' => $item->vip,
                    'vipCard' => $item->vipCard,
                    'vipCreateTime' => $item->vipCreateTime,
                    'region' => $item->regionFormat,
                    'label' => $item->label,
                    'notes' => $item->notes,
                    'state' => $item->state,
                    'subscribe' => $item->subscribe(),
                    'miniOpenid' => $item->getMiniOpenid() ?: '',
                    'wechatOpenid' => $item->getWechatOpenid() ?: '',
                    'unionid' => $item->getUnionid() ?: '',
                    'registerStore' => $item->registerStoreData,
                    'payTime' => $item->payTime,
                    'bindList'              =>  collect($this->memberBind)->pluck('type'),
                    'created_at'            =>  $item->created_at->format('Y-m-d H:i:s'),
                    'updated_at'            =>  $item->updated_at->format('Y-m-d H:i:s'),
                ];
            }),
            'total' => $this->total(), // 数据总数
            'pageSize' => $this->perPage(), // 每页数量
            'pageNo' => $this->currentPage(), // 当前页码
        ];
    }
}
