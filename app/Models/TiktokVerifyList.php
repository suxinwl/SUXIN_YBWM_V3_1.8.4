<?php
namespace App\Models;
use App\Models\Store;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
class TiktokVerifyList extends BaseModel{
    protected $table = 'tiktok_verify_list';
    use HasFactory;
    protected $guarded = [];
    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId');
    }
    public function admin()
    {
        return $this->hasOne(Admin::class, 'id', 'userId');
    }
}
