<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Smscombo extends BaseModel
{
    use HasFactory;
    protected $table = 'sms_combo';
    protected $fillable = ['sort','num','price','linePrice','state'];
    // protected $guard = 'admin';
    protected $attributes  =  [
        'sort' => 0,
        'num' => 0,
        'price' => 0,
        'linePrice' => 0
    ];
}
