<?php

namespace App\Models\Tables;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends BaseModel
{
    use HasFactory;
    protected $table = 'table_area';
    protected $fillable = [
        'uniacid', 'storeId', 'name','sort'
    ];
}
