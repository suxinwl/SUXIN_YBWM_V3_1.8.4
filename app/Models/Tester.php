<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tester extends BaseModel
{
    use HasFactory;
    protected $table = 'mini_tester';
    protected $guarded = [];
}
