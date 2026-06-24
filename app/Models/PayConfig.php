<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayConfig extends BaseModel
{
    protected $table = 'pay_config';
    protected $guarded = [];
    use HasFactory;

    protected $appends = [
        'data'
    ];
    protected $hidden = [
        'payTemplate'
    ];
    
    public function payTemplate()
    {
        return $this->hasOne(PayTemplate::class, 'id', 'templateId');
    }

    public function getDataAttribute()
    {
        return $this->payTemplate->data;
    }
}
