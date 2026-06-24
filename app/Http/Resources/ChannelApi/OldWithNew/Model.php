<?php

namespace App\Http\Resources\ChannelApi\OldWithNew;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ChannelApi\OldWithNew\PartyA;
use App\Models\Coupon\Coupon;

class Model extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $partyA = $this->partyA;
        if ($partyA['type'] == 3) {
            $partyA['giveData'] = collect($partyA['giveData'])->map(function ($giveData) {
                $giveData['couponData'] = collect($giveData['couponData'])->map(function ($coupon) {
                    $coupon['coupon'] = Coupon::find($coupon['id']);
                    return $coupon;
                });
                return $giveData;
            });
        } else {
            $partyA['giveData']['couponData'] = collect($partyA['giveData']['couponData'])->map(function ($coupon) {
                $coupon['coupon'] = Coupon::find($coupon['id']);
                return $coupon;
            });
        }
        $partyB = $this->partyB;
        $partyB['partyB']['couponData'] = collect($partyB['partyB']['couponData'])->map(function ($coupon) {
            $coupon['coupon'] = Coupon::find($coupon['id']);
            return $coupon;
        });
        $partyB['firstPay']['couponData'] = collect($partyB['firstPay']['couponData'])->map(function ($coupon) {
            $coupon['coupon'] = Coupon::find($coupon['id']);
            return $coupon;
        });
        return [
            'id' => $this->id,
            'uniacid' => $this->uniacid,
            'title' => $this->title,
            'partyA' => $partyA,
            'partyB' => $partyB,
            'contents' => $this->contents,
            'startTime' => $this->startTime,
            'endTime' => $this->endTime,
            'partyAPage' => $this->partyAPage,
            'partyBPage' => $this->partyBPage,
            'shearPage' => $this->shearPage,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'subState'=>$this->subState,
            'newGiftSwitch' => $this->newGiftSwitch
        ];
    }
}
