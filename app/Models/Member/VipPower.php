<?php

namespace App\Models\Member;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VipPower extends BaseModel
{
    protected $table = 'member_vip_power';
    use HasFactory;
    protected $fillable = ['sort','storeId', 'uniacid', 'icon', 'name', 'showName', 'desc', 'state'];

    
}
