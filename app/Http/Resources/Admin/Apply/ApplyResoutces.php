<?php

namespace App\Http\Resources\Admin\Apply;

use Illuminate\Http\Resources\Json\JsonResource;

class ApplyResoutces extends JsonResource
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
            'applyName'              =>  $this->applyName,
            'applyImage'              =>  $this->applyImage,
            'startTime' => $this->startTime,
            'endTime' => $this->endTime,
            'plugType' => $this->plugType,
            'plugStr' => $this->plugStr,
            'timeType' => $this->timeType,
            'createUserId' => $this->createUserId,
            'attachmentType' => $this->attachmentType,
            'attachmentData' => $this->attachmentData,
            "copyrightSwitch" => $this->copyrightSwitch,
            'copyright' => $this->copyright,
            'address' => $this->address,
            'addressFormat' => $this->addressFormat() ?? '',
            'smsAccount' => $this->smsAccount,
            'deliveryChannel' => $this->deliveryChannel,
            'musterId' => $this->musterId,
            'day' => $this->day,
            'muster' => $this->muster,
            'sort' => $this->sort,
            'notes' => $this->notes,
            'smsSign' => $this->smsSign,
            'memberCount' => $this->memberCount(),
            'orderCount' => $this->orderCount(),
            'storeCount' => $this->storeCount(),
            'storeNumInfinite'=>$this->storeNumInfinite,
            'storeNum'=>$this->storeNum,
            'desc'=>$this->notes,
            'username' => $this->admin->username . "({$this->admin->nickname})",
            'status'                    =>  $this->status,
            'created_at'            =>  $this->created_at->format('Y-m-d H:i:s'),
            'updated_at'            =>  $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
