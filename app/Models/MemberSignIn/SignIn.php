<?php

namespace App\Models\MemberSignIn;

use App\Events\SignInEvent;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

class SignIn extends BaseModel
{
    protected $table = 'sign_in';
    use HasFactory;
    protected $fillable = ['uniacid', 'userId', 'day', 'daily', 'plusRewards', 'storeId'];
    protected $casts =  [
        'daily' => 'array',
        "plusRewards" => "array"
    ];


    public static function boot()
    {
        parent::boot();
        static::created(function ($model) {
            Event(new SignInEvent($model));
            return true;
        });
    }
}
