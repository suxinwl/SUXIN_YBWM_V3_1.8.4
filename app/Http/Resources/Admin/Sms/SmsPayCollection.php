<?php

namespace App\Http\Resources\Admin\Sms;

use App\Enums\PayEnum;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SmsPayCollection extends ResourceCollection
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
                if ($item->user->id == 1) {
                    $username =  $item->user->username . "(超级管理员)";
                } else {
                    $username = empty($item->user->nickname) ? $item->user->username : $item->user->username . "({$item->user->nickname})";
                }
                $temp = $item->toArray();
                $temp['applyName'] = $item->uniacid == 0 ? '总后台' : $item->apply->applyName;
                $temp['payForamt'] = PayEnum::format($item->source);
                $temp['username'] = $username;
                return $temp;
            }),
            'total' => $this->total(), // 数据总数
            'pageSize' => $this->perPage(), // 每页数量
            'pageNo' => $this->currentPage(), // 当前页码
        ];
    }
}
