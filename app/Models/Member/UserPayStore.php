<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPayStore extends Model
{
    use HasFactory;
    protected $table = 'user_pay_store';
    protected $guarded=[];
}
