<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SingIn extends BaseModel
{
    use HasFactory;
    protected $table = 'sign_in';
    protected $guarded = [];

    protected $appends = [
    ];
}
