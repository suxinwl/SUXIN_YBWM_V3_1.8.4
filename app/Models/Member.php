<?php

namespace App\Models;

use App\Events\BirthdayGiftEvent;
use App\Models\Admin\Apply;
use App\Models\Coupon\Coupon;
use App\Models\Coupon\MemberCoupon;
use App\Models\EquityCard\Member as EquityCardMember;
use App\Models\Member\Group;
use App\Models\Member\MemberQrCode;
use App\Models\Member\Vip;
use App\Models\Store\StoreBase;
use App\Services\ConfigService;
use App\Services\OpenWechat\ChannelOpenWechat;
use Cache;
use Event;
use Hhxsv5\LaravelS\Swoole\Timer\CronJob;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class Member extends Authenticatable implements JWTSubject
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'member';
    protected $hidden = [
        'password'
    ];
    protected $casts =  [
        "labelId" => 'array',
        'region' => 'array',
    ];
    protected $appends = [
        'profix', 'regionFormat','avatar'
    ];
    protected $_memberBindOne;
    /**
     * Prepare a date for array / JSON serialization.
     *
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
    protected $fillable = [
        'notes','partnerId', 'sex', 'groupId', 'storeId', 'isCircleAuditor', 'notes', 'isTalentShow', 'labelId', 'mobile', 'username', 'password', 'state', 'uniacid', 'score', 'nickname', 'avatar', 'realname', 'birthday'
    ];

    public function  apply()
    {
        return $this->hasOne(Apply::class, 'id', 'uniacid');
    }
    public function getAvatarAttribute()
    {
        if ($this->attributes['avatar']) {
            return $this->attributes['avatar'];
        } else {
            return $this->apply->applyImage;
        }
    }

    public function partnerOrder(){
        return $this->hasMany(PartnerOrder::class,'userId','id');
    }

    /**
     * 查询用户的时候name字段处理
     *
     * @author Eric
     * @param $value
     * @return string
     */
    public function getProfixAttribute()
    {
        return $this->MemberBindOne();
    }

    public function coupons()
    {
        return $this->hasMany(MemberCoupon::class, 'userId', 'id')->where('state', 1);
    }
    public function partner()
    {
        return $this->hasOne(Member::class, 'id', 'partnerId')->select(['id', 'mobile', 'nickname']);
    }
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }


    public function MemberBind()
    {
        return $this->hasMany(MemberBind::class, 'userId', 'id');
    }
    public function MemberBindOne()
    {
        if (!$this->_memberBindOne) {
            $this->_memberBindOne = MemberBind::where('userId', $this->id)->whereNotIn('type', [5, 6])->first();
        }
        return $this->_memberBindOne;
    }

    public function account()
    {
        return $this->hasOne(MemberAccount::class, 'userId', 'id');
    }



    public function getScoreFormatAttribute()
    {
        return appTypeFormat($this->score);
    }

    public function sexFormat()
    {
        $data = [
            1 => '男',
            2 => '女',
        ];
        return isset($data[$this->sex]) ? $data[$this->sex] : "不详";
    }
    public function getOpenId()
    {
        return Cache::get("userLoginOpenid:" . appType(Request()->header('appType', 'mini')) . ':' . $this->id);
    }

    public function getWechatOpenId()
    {
        $model = MemberBind::where('userId', $this->id)->where('type', 2)->first();
        if ($model) {
            return $model->openid;
        }
        return null;
    }

    public function label()
    {
        return $this->belongsToMany(MemberLabel::class, 'member_label_ids', 'userId', 'labelId');
    }


    public function group()
    {
        return $this->hasOne(Group::class, 'id', 'groupId');
    }

    public function store()
    {
        return $this->hasOne(StoreBase::class, 'id', 'storeId')->select(['id', 'name', 'isolate']);
    }

    public function registerStoreData()
    {
        return $this->hasOne(Store::class, 'id', 'registerStore')->select(['id', 'name']);
    }

    public function payStore()
    {
        return $this->hasOne(Store::class, 'id', 'payStoreId')->select(['id', 'name']);
    }


    public function subscribe()
    {
        $model = MemberSubscribe::where('unionid', $this->getUnionid())->first();
        return  !empty($model) && $model->subscribe == 1 ? true : false;
    }

    public function getMiniOpenId()
    {
        $model = MemberBind::where('userId', $this->id)->where('type', 1)->first();
        if ($model) {
            return $model->openid;
        }
        return null;
    }

    public function getUnionid()
    {
        $model = MemberBind::where('userId', $this->id)->whereIn('type', [1, 2])->first();
        if ($model) {
            return $model->unionid;
        }
        return null;
    }



    public static function boot()
    {
        parent::boot();
        static::creating(function ($member) {
            $member->avatar = null;
        });
        static::created(function ($member) {
            MemberAccount::create([
                'uniacid' => $member->uniacid,
                'userId' => $member->id
            ]);
        });
    }


    public function scopeTourists($query)
    {
        return  $query->where(function ($query) {
            return $query->whereNull('mobile')->orWhere('mobile', '=', '');
        });
    }

    public function scopeMembers($query)
    {
        $query->where('mobile', '!=', '');
    }

    public function vip()
    {
        return $this->hasOne(Vip::class, 'id', 'vipId');
    }

    public function birthdayGift()
    {
        return $this->hasOne(BirthdayPack::class, 'userId', 'id')
            ->where("year", Carbon::now()->format('Y'))->where('type', 2);
    }


    public function getIsBirthdayAttribute()
    {
        if ($this->birthday && !$this->birthdayGift) {
            $config = ConfigService::getChannelConfig('birthdayGift', $this->uniacid);
            if ($config['birthday'] && $config['birthday']['switch'] == 1) {
                $um = Carbon::createFromFormat("Y-m-d", $this->birthday)->format('m');
                $ud = Carbon::createFromFormat("Y-m-d", $this->birthday)->format('d');
                $m = Carbon::now()->format('m');
                $d = Carbon::now()->format('d');
                if ($m == $um && $d == $ud) {
                    $config['birthday']['couponGive'] = Coupon::whereIn('id', collect($config['birthday']['couponList'])->pluck('id') ?? [])->get();
                    return $config['birthday'];
                }
            }
        }
        return null;
    }

    public function initVip()
    {
        $vip = Vip::where("uniacid", $this->uniacid)
            ->where('storeId', $this->storeId)
            ->orderBy('level', 'asc')->first();
        return $vip ? $vip->id : 0;
    }

    public function getRegionFormatAttribute()
    {
        if (!empty($this->region)) {
            $list =  Region::select('name')->whereIn('id', $this->region)->get();
            $list = collect($list)->pluck('name')->toarray();
        }
        return empty($list) ? '' : implode('/', $list);
    }

    /**
     * 判断用户身份
     */
    public function getRuleAttribute()
    {
        if (empty($this->mobile)) {
            return 2;
        } elseif ($this->payOrder == 0) {
            return 3;
        } else {
            return 4;
        }
    }

    public function getNewSubAttribute()
    {
        return $this->payOrder;
    }

    public function getNewUserAttribute()
    {
        return $this->payOrder == 0;
    }

    public function equityCard()
    {
        return $this->hasOne(EquityCardMember::class, 'userId', 'id')
            ->where('endTime', ">=", Carbon::now()->toDateTimeString())
            ->with(['equityCard' => function ($q) {
                return $q->select([
                    'id', 'name', 'desc', 'imageType', 'image', 'textColor', 'themeColor'
                ]);
            }])
            ->orderBy('id', 'desc');
    }

    public function member()
    {
        return $this->hasOne(Store::class, 'id', 'storeId');
    }
}
