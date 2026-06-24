<?php

namespace App\Models\Member;

use App\Jobs\MemberJob;
use App\Models\BaseModel;
use App\Models\Coupon\Coupon;
use App\Models\Member;
use App\Models\MemberLabel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends BaseModel
{
    use HasFactory;
    protected $table = 'member_job';
    protected $fillable = ['name', 'uniacid', 'storeId', 'changeType', 'type', 'value', 'jobType', 'state', 'notes', 'data', 'memberCount', 'success'];

    protected $appends = [
        'dataList', 'typeFormat', 'jobTypeFormat', 'changeTypeFormat','couponList'
    ];
    protected $casts =  [
        'data' => 'array',
        'value' => 'array'
    ];
    public function getCouponListAttribute()
    {
        if ($this->type == 4) {
            if($this->value){
                $ids=array_column($this->value,'id');
                if(is_array($ids)){
                    return  Coupon::whereIn('id', $ids)->get();
                }
            }
        }
        return [];
    }
    public function getDataListAttribute()
    {
        if ($this->jobType == 1) {
            return  MemberLabel::whereIn('id', $this->data)->get();
        }
        if ($this->jobType == 2) {
            return Group::whereIn('id', $this->data)->get();
        }
        return [];
    }

    public function getTypeFormatAttribute()
    {
        $data = [
            1 => '积分调整',
            2 => '余额调整',
            3 => '成长值调整',
            4 => '批量发券'
        ];
        return  $data[$this->type];
    }

    public function getChangeTypeFormatAttribute()
    {
        $data = [
            2 => '减少',
            1 => '增加',
        ];
        return  $data[$this->changeType];
    }

    public function getJobTypeFormatAttribute()
    {
        $data = [
            1 => '按会员标签',
            2 => "按会员分组",
            3 => "手动导入用户",
        ];

        return  $data[$this->jobType];
    }


    public static function boot()
    {
        parent::boot();
        static::saved(function ($model) {
            if ($model->state == 0 && $model->jobType != 3) {
                $users = Member::where('uniacid', $model->uniacid)
                    ->where(function ($q) use ($model) {
                        if ($model->jobType == 1) {
                            return  $q->whereHas('label', function ($q) use ($model) {
                                return $q->whereIn('labelId', $model->data);
                            });
                        } elseif ($model->jobType == 2) {
                            return $q->whereIn('groupId', $model->data);
                        } elseif ($model->jobType == 3) {
                            return $q->where(1, 0);
                        } elseif ($model->jobType == 4 || $model->jopType == 4) {
                            return $q->whereIn('id', $model->data);
                        } else {
                            return $q->where('id', 0);
                        }
                    })->groupBy('id')->get();
                $model->memberCount = collect($users)->count();
                $model->state = 1;
                $model->saveQuietly();
                collect($users)->map(function ($user, $key) use ($model) {
                    dispatch(new MemberJob($user->id, $model->id, $key + 1, $model->changeType, $model->value));
                });
            }
        });
    }
}
