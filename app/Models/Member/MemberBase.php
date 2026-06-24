<?php

namespace App\Models\Member;

use App\Events\BirthdayGiftEvent;
use App\Models\Admin\Apply;
use App\Models\BaseModel;
use App\Models\Coupon\Coupon;
use App\Models\Coupon\MemberCoupon;
use App\Models\Member\Group;
use App\Models\Member\MemberQrCode;
use App\Models\Member\Vip;
use App\Models\Partner;
use App\Services\ConfigService;
use App\Services\OpenWechat\ChannelOpenWechat;
use Cache;
use Event;
use Hhxsv5\LaravelS\Swoole\Timer\CronJob;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use App\Models\PartnerOrder;
class MemberBase extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'member';
    protected $hidden = [
        'password', 'apply'
    ];
    protected $appends = [
        'avatar'
    ];

    public function partner()
    {
        return $this->hasOne(MemberBase::class, 'id', 'partnerId')->select(['id', 'mobile', 'nickname']);
    }

    public function getAvatarAttribute()
    {
        if ($this->attributes['avatar']) {
            return $this->attributes['avatar'];
        } else {
            return $this->apply->applyImage;
        }
    }
    public function  apply()
    {
        return $this->hasOne(Apply::class, 'id', 'uniacid');
    }

    public function partnerOrder(){
        return $this->hasMany(PartnerOrder::class,'userId','id');
    }
}
