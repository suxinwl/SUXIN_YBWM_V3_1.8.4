<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShortLink extends Model
{
    use HasFactory;
    protected $table = 'short_link';
    protected $fillable = ['uniacid', 'storeId', 'shortLink', 'wx', 'ali', 'type', 'ident'];
    protected $casts =  [
        'wx' => 'array',
        'ali' => 'array'
    ];

}
