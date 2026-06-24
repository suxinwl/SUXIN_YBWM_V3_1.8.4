<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BirthdayPack extends Model
{
    protected $table = 'birthday_pack';
    use HasFactory;
    protected $casts =  [
        'data' => 'array',
    ];
    protected $fillable = [
        "uniacid", 'userId', 'type', 'year', 'data','storeId'
    ];
}
