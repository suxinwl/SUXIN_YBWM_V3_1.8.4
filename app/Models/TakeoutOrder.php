<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Store;
class TakeoutOrder extends BaseModel
{
    use HasFactory;
    public $_store;
    protected $table = 'takeout_order';
    protected $guarded = [];
    protected $appends = [
        'store'
    ];

    public  function getStoreAttribute()
    {
        if (!$this->_store) {
            $this->_store = Store::where('id', $this->storeId)->select('id','name')->first();
        }
        return $this->_store;
    }
}
