<?php

namespace App\Models\Drinks;

use App\Models\Admin;
use App\Models\BaseModel;
use App\Models\Member\MemberBase;
use App\Models\Store\StoreBase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Order extends BaseModel
{
    use HasFactory;
    protected $table = 'drinks_order';
    protected $fillable = [
        'score', 'adminId', 'sort', 'uniacid', 'residue', 'storeId', 'userId', 'drinksId', 'num', 'state', 'expiredTime', 'orderSn', 'contacts', 'mobile', 'notes'
    ];

    protected $appends = [
        'stateFormat', 'admin', 'scoreFormat', 'expiredTimeFormat','typeFormat'
    ];

    public function drink()
    {
        return $this->hasOne(Drinks::class, 'id', 'drinksId')->withTrashed();
    }

    public function user()
    {
        return $this->hasOne(MemberBase::class, 'id', 'userId')->withTrashed()->select(['id', 'nickname', 'mobile']);
    }

    public function store()
    {
        return $this->hasOne(StoreBase::class, 'id', 'storeId')->select(['id', 'name']);
    }

    public function getScoreFormatAttribute()
    {
        return appTypeFormat($this->score);
    }

    public function getAdminAttribute()
    {
        return Admin::select(['id', 'username', 'mobile', 'nickname'])->find($this->adminId);
    }

    public function getStateFormatAttribute()
    {
        $data = [
            1 => '存储中',
            2 => "全部取出",
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
    public function getExpiredTimeFormatAttribute()
    {
        return $this->expiredTime ? Carbon::createFromFormat('Y-m-d H:i:s', $this->expiredTime)->format('Y-m-d H:i') : '-';
    }


    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            if (!$model->exists) {
                $model->orderSn = getTakeOutNo();
                $model->residue = $model->num;
                $model->expiredTime = Carbon::now()->addDays($model->drink->day)->toDateTimeString();
            }
        });
    }
}
