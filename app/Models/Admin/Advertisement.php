<?php

namespace App\Models\Admin;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Advertisement extends BaseModel
{
    use HasFactory;
    protected $table = 'advertisement';

    protected $fillable = ['material_type','sort','content','title','display','subTitle','icon'];
}
