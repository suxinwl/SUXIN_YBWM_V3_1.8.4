<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsAccount extends BaseModel
{
    use HasFactory;
    protected $table = 'sms_account';
    protected $guarded = [];

    protected $appends = [
        'totalCount'
    ];

    public function getTotalCountAttribute()
    {
        return $this->count + $this->send_num;
    }
}
