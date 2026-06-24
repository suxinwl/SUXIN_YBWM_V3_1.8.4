<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Hardware extends BaseModel
{
    use HasFactory;
    protected $table = 'hardware';
    protected $fillable = [
        'uniacid', 'storeId', 'type', 'vendor', 'config', 'ruleId', 'sort', 'notes'
    ];

    protected $casts =  [
        'config' => 'array',
    ];

    public function rule(){
        return $this->hasOne(PrintRule::class,'printId','id');
    }
}
