<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class StoredValue extends Model
{
    use HasFactory;
    protected $table = 'stored_value';
    protected $fillable = [
        'name', "amount", 'uniacid', 'storeId', 'giveData', 'sort','storeId'
    ];

    protected $appends = [
        'first', 'rule'
    ];

    protected $casts =  [
        'giveData' => 'array',
    ];
    protected $attributes = [
        'state' => 1
    ];

    public function order()
    {
        return $this->hasMany(StoredValueOrder::class, 'storeValueId', 'id')->where('state', 2);
    }

    public function getFirstAttribute()
    {
        if ($this->giveData['first']['state'] != 1) {
            return 1;
        }
        $appType = appType(Request()->header('appType','pc'));
        if($appType ==1){
            $userId = auth('user')->user()->id;
        }elseif($appType == 10){
            $userId = Request()->userId;
        }
        if ($userId) {
            $key = "storedValue:{$this->id}:{$userId}";
            return Cache::has($key) ? Cache::get($key) : 0;
        }
        return 0;
    }

    public function getRuleAttribute()
    {
        if (!$this->exists) {
            return [];
        }
        if ($this->first == 0 && $this->giveData['first']['state'] == 1) {
            return $this->giveData['first'];
        } else {
            return $this->giveData['common'];
        }
    }
}
