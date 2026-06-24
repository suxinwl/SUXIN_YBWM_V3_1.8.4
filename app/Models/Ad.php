<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ad extends BaseModel
{
    use HasFactory;
    protected $table = 'ad';
    protected $fillable = ['storeIds', 'countType', 'count', 'sort', 'name', 'userType', 'startTime', 'endTime', 'type', 'location', 'data', 'storeType', 'storeId'];
    
    protected $casts =  [
        'data' => 'array',
        'storeIds' => 'array',
    ];

    protected $attributes =  [
        'userType' => 1,
        'storeType' => 1,
    ];

    protected $appends = [
        'typeFormat', 'locationFormat', 'storeTypeFormat', 'state', 'userTypeFormat'
    ];

    public function getTypeFormatAttribute()
    {
        $data = [
            1 => "弹窗广告",
            2 => "banner图"
        ];
        return $data[$this->type];
    }

    public function getTypeKeyAttribute()
    {
        $data = [
            1 => "window",
            2 => "banner"
        ];
        return $data[$this->type];
    }

    public function getLocationFormatAttribute()
    {
        $data = [
            1 => "首页",
            2 => "点单页",
            3 => "个人中心",
            4 => "订单页"
        ];
        return $data[$this->location];
    }

    public function getLocationKeyAttribute()
    {
        $data = [
            1 => "index",
            2 => "goods",
            3 => "user",
            4 => "orderInfo"
        ];
        return $data[$this->location];
    }

    public function getStoreTypeFormatAttribute()
    {
        $data = [
            1 => "全部门店",
            2 => "指定门店适用",
            3 => "指定门店不适用"
        ];
        return $data[$this->storeType];
    }

    public function getUserTypeFormatAttribute()
    {
        $data = [
            1 => "全部用户",
            2 => "游客",
            3 => "新用户",
            4 => "老用户"
        ];
        return $data[$this->userType];
    }

    public function getStateAttribute()
    {
        if (time() < strtotime($this->startTime)) {
            return "未开始";
        } elseif (time() >= strtotime($this->startTime) && time() <= strtotime($this->endTime)) {
            return "进行中";
        } else {
            return "已结束";
        }
    }
}
