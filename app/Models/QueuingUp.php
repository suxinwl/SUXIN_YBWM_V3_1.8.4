<?php

namespace App\Models;

use App\Models\Tables\Type;
use App\Services\ConfigService;
use App\Services\InStoreOrderService;
use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB as FacadesDB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;

class QueuingUp extends BaseModel
{
    use HasFactory, SoftDeletes;
    protected $table = 'queuing_up';
    protected $fillable = [
        'uniacid', 'storeId', 'userId', 'type_id', 'serialNum', 'price', 'people', 'contact', 'note', 'state', 'created_at', 'updated_at', 'number', 'day', 'deleted_at', 'score'
    ];

    protected $appends = [
        'statusFormat', 'waitingTime', 'count', 'scoreFormat', 'waitingTable'
    ];
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }


    public static function statusList()
    {
        return [
            1 => '已取号',
            2 => '已过号',
            3 => '已就餐',
        ];
    }

    public function getStatusFormatAttribute()
    {
        if ($this->deleted_at) {
            return "已过号";
        } else {
            $data = self::statusList();
            return $data[$this->state];
        }
    }


    public function getscoreFormatAttribute()
    {
        return appTypeFormat($this->score);
    }

    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId')->select(['id', 'name']);
    }

    public function type()
    {
        return $this->hasOne(Type::class,  'id', 'type_id');
    }

    public function getWaitingTimeAttribute()
    {
        if ($this->state == 1) {
            return Carbon::now()->diffInMinutes($this->created_at, true);
        } else {
            return Carbon::createFromFormat('Y-m-d H:i:s', $this->created_at)->diffInMinutes($this->updated_at, true);
        }
    }

    public function getSerialNum()
    {
        $key = "serialNum:{$this->uniacid}:{$this->storeId}:{$this->type_id}";
        $tag = "serialNum:store:{$this->storeId}";
        if (Cache::tags($tag)->has($key)) {
            $pickNo = intval(Cache::tags($tag)->get($key) + 1);
            Cache::tags($tag)->set($key, $pickNo);
        } else {
            $pickNo = 1;
            Cache::tags($tag)->set($key, $pickNo);
        }
        return $pickNo;
    }

    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            if (!$model->exists) {
                $model->day = Carbon::now()->toDateTimeString();
            }
            return true;
        });
        static::deleting(function ($model) {
            $model->state = 2;
            return true;
        });
    }

    public function getCountAttribute()
    {
        return DB::table('queuing_up')->where('uniacid', $this->uniacid)->where('storeId', $this->storeId)->where('day', $this->day)->count();
    }

    public function getQrcodeAttribute()
    {
        return Request()->getSchemeAndHttpHost() . "/s/queuingUp/" . $this->uniacid . '/?id=' . $this->id;
    }

    public function getWaitingTableAttribute()
    {
        return DB::table('queuing_up')
            ->where('id', "<", $this->id)
            ->where('uniacid', $this->uniacid)
            ->where('storeId', $this->storeId)
            ->where('state', 1)
            ->whereNull('deleted_at')
            ->where('day', $this->day)->count();
    }
}
