<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Member;
class SignList extends BaseModel
{
    protected $table = 'sign_list';
    protected $guarded = [];
    use HasFactory;
    use SoftDeletes;
    public function member()
    {
        return $this->hasMany(Member::class, 'id', 'userId');
    }
}
