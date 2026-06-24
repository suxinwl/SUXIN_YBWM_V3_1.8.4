<?php

namespace App\Http\Resources\ChannelApi\User;

use Illuminate\Http\Resources\Json\JsonResource;

class Profix extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'                    =>  $this->id,
            'nickname'              =>  $this->nickname,
            'avatar'                =>  $this->avatar ?? $this->apply->applyImage,
            "mobile"                =>  $this->mobile,
            'account'               =>  $this->account,
            'vipCard'               => $this->vip,
            'vipCardNum' => $this->vipCard,
            'nextVip'               => $this->vip->nextVip,
            'applyName'             => $this->apply->applyName,
            'applylogo'             => $this->apply->applyImage,
            'realName' =>  $this->realname,
            'birthday' =>  $this->birthday,
            'birthdayPack' => $this->isBirthday,
            'sex' =>  $this->sex,
            'newUser' =>  $this->newUser,
            'coupons' => collect($this->coupons)->count(),
            'equityCard' => $this->equityCard,
            'created_at' => $this->created_at,
            'openid' => $this->getOpenId(),
            'isolate' => $this->store->isolate ?? 0
        ];
    }
}
