<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CostomPay extends BaseModel
{
    use HasFactory, SoftDeletes;
    protected $table = 'costom_pay';
    protected $fillable = [
        "uniacid", "uniacid", "name", "logo", "state", "sort"
    ];


    public function getPayIdAttribute()
    {
        return "100{$this->id}";
    }
}
