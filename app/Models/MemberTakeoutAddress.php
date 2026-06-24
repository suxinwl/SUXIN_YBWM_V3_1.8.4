<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemberTakeoutAddress extends BaseModel
{
    use HasFactory, SoftDeletes;
    protected $table = 'member_takeout_address';
    protected $fillable  = [
         'uniacid', 'userId', 'address', 'contact', 'lat', 'lng', 'mobile', 'call', 'label', 'isDefault'
    ];

}
