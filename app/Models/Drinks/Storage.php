<?php

namespace App\Models\Drinks;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Storage extends Model
{
    use HasFactory;
    protected $table = 'drinks_storage';
    protected $fillable = [
        'sort', 'uniacid', 'storeIds', 'name', 'logo', 'unit', 'day', 'desc', 'state'
    ];
    protected $casts =  [
        'storeIds' => 'array',
    ];
    protected $appends = [
        'store'
    ];

    public function getStoreAttribute()
    {
        return DB::table('store')->select(['id', 'name'])->whereIN('id', $this->storeIds)->get();
    }
}
