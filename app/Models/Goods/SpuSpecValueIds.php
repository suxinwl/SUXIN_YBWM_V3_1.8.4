<?php

namespace App\Models\Goods;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpuSpecValueIds extends BaseModel
{
    protected $table = 'spu_specvalueids';
    use HasFactory;
    protected $with = [];
    protected $guarded = [];
}
