<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ceshi extends BaseModel
{
    use HasFactory;
    protected $table = 'ceshi';
    protected $fillable =['a','b','c','d'];
}
