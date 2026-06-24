<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsMark extends BaseModel
{
    protected $table = 'goods_mark';
    use HasFactory;
    protected $fillable = ['name', 'sort', 'bgColor', 'startTime', 'endTime','storeId'];
}
