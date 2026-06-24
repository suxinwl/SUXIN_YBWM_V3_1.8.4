<?php

namespace App\Models\Handover;

use App\Models\Admin;
use App\Models\BaseModel;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Handover extends BaseModel
{
    use HasFactory;
    protected $table = 'handover';
    protected $fillable = [
        'uniacid', 'storeId', 'adminId', 'state', 'startTime', 'endTime', 'contents'
    ];
    protected $with =  ['admin'];
    protected $casts =  [
        'contents' => 'array',
    ];
    public function admin(){
        return $this->hasOne(Admin::class,'id','adminId');
    }
    public function store(){
        return $this->hasOne(Store::class,'id','storeId');
    }
    public function getContentsAttribute()
    {
        if ($this->attributes['contents']) {
            return json_decode($this->attributes['contents']);
        }else{
            return new Contents([
                'startTime' => $this->startTime,
                'endTime' => $this->endTime,
                'adminId' => $this->adminId,
                'uniacid' => $this->uniacid,
                'storeId' => $this->storeId,
            ]);
        }
    }

    public function getEndTimeAttribute()
    {
        if ($this->attributes['endTime']) {
            return $this->attributes['endTime'];
        }
        return date("Y-m-d H:i:s",time());
    }
}
