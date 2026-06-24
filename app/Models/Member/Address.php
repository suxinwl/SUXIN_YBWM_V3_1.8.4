<?php

namespace App\Models\Member;

use App\Models\BaseModel;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Address extends BaseModel
{
    use HasFactory, SoftDeletes;
    protected $table = 'member_address';
    protected $fillable  = [
        'description', 'uniacid', 'userId', 'address', 'contact', 'lat', 'lng', 'mobile', 'call', 'label', 'isDefault', 'regionId','province','city','district'
    ];
    protected $casts =  [
        'regionId' => 'array',
    ];

    protected $append = [
        'regionFormat'
    ];


    public function getRegionFormatAttribute()
    {
        if (!empty($this->regionId)) {
            $list =  Region::select('name')->whereIn('code', $this->regionId)->get();
            $list = collect($list)->pluck('name')->toarray();
        }
        return empty($list) ? '' : $list;
    }

    public static function boot()
    {
        parent::boot();
        static::created(function ($address) {
            if ($address->isDefault == 1) {
                Address::where('uniacid', $address->uniacid)->where('userId', $address->userId)->whereNotIn('id', [$address->id])->update(['isDefault' => 0]);
            }
        });

        static::updating(function ($address) {
            if ($address->isDefault == 1) {
                Address::where('uniacid', $address->uniacid)->where('userId', $address->userId)->update(['isDefault' => 0]);
            }
        });

        static::saving(function ($address) {
            if ($address->isDefault == 1) {
                Address::where('uniacid', $address->uniacid)->where('userId', $address->userId)->update(['isDefault' => 0]);
            }
        });
    }
}
