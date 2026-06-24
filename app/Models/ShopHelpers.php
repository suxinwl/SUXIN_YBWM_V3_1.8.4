<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopHelpers extends BaseModel
{
    use HasFactory;
    protected $table = 'shop_helpers';
    protected $fillable = ['uniacid','sort','name','body','state'];
}
