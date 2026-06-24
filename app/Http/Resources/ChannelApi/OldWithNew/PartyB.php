<?php

namespace App\Http\Resources\ChannelApi\OldWithNew;

use App\Models\Coupon\Coupon;
use Illuminate\Http\Resources\Json\JsonResource;

class PartyB extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array.
     * {"type":1,"giveData":{"person":1,"couponSwitch":1,"integralSwitch":1,"couponData":[{"type":5,"name":"\u56fa\u5b9a\u4ef7\u683c\u5238","id":38,"num":1}],"integral":"12"}}
     */
    public function toArray($request)
    {
        $data = $this->data;
        if ($data['partyB']['couponData']) {
            $data['partyB']['couponData'] = collect($data['partyB']['couponData'])->map(function ($coupon) {
                $coupon['coupon'] = Coupon::find($coupon['id']);
                return $coupon;
            });
        }
        if ($data['firstPay']['couponData']) {
            $data['firstPay']['couponData'] = collect($data['firstPay']['couponData'])->map(function ($coupon) {
                $coupon['coupon'] = Coupon::find($coupon['id']);
                return $coupon;
            });
        }
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'oldWithNewId' => $this->oldWithNewId,
            'data' => $data,
            'partyAid' => $this->partyAid,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
            'partyBstate' => $this->partyBstate,
            'partyAUser'=>$this->partyAUser,
            'firstPayState' => $this->firstPayState,
        ];
    }
}
