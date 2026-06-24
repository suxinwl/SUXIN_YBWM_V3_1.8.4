<?php

namespace App\Models\Drinks;

use App\Models\Admin;
use App\Models\BaseModel;
use App\Models\Member\MemberBase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use App\Models\Store;
use App\Services\OrderService;

class Log extends BaseModel
{
    use HasFactory;
    protected $table = 'drinks_log';
    protected $fillable = [
        'sort', 'uniacid', 'storeId', 'userId', 'drinksId', 'drinksOrderId', 'num', 'score', 'adminId', 'type', 'residue', 'orderSn'
    ];

    protected $appends = [
        'stateFormat', 'admin', 'scoreFormat', 'typeFormat'
    ];

    public function drink()
    {
        return $this->hasOne(Drinks::class, 'id', 'drinksId')->withTrashed();
    }

    public function order()
    {
        return $this->hasOne(Order::class, 'id', 'drinksOrderId');
    }

    public function user()
    {
        return $this->hasOne(MemberBase::class, 'id', 'userId')->withTrashed()->select(['id', 'nickname', 'mobile']);
    }
    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId')->withTrashed()->select(['id', 'name', 'mobile']);
    }
    public function getScoreFormatAttribute()
    {
        return appTypeFormat($this->score);
    }

    public function getAdminAttribute()
    {
        if (in_array($this->score, [9, 10, 11])) {
            return Admin::select(['id', 'username', 'mobile', 'nickname'])->find($this->adminId);
        } else {
            return $this->user;
        }
    }

    public function getStateFormatAttribute()
    {
        $data = [
            0 => '待审核',
            1 => '正常',
            2 => '已拒绝',
            3 => "已过期"
        ];
        return $data[$this->state];
    }

    public function getTypeFormatAttribute()
    {
        $data = [
            1 => '寄存',
            2 => "取出",
            3 => "过期"
        ];
        return $data[$this->type];
    }

    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            if (!$model->exists) {
                if (!$model->orderSn) {
                    $model->orderSn = getTakeOutNo();
                }
            }
        });
        static::saved(function ($model) {
            OrderService::otherPrintOrder(3, $model);
        });
    }
}
