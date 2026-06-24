<?php

namespace App\Models\Mini;

use App\Models\BaseModel;
use App\Models\Mini\ApplyMiniPath;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class MiniPath extends BaseModel
{
    protected $table = 'mini_path';
    protected $fillable = ['name', 'path','type'];
    use HasFactory;
    protected $appends = [
        'url','wxState'
    ];
    public function wx()
    {
        return $this->hasOne(ApplyMiniPath::class, 'type', 'type')->where('channel',1);
    }

    public function getUrlAttribute()
    {
        return Request()->getSchemeAndHttpHost() . "/s/{$this->type}/" . Request()->header('uniacid') . '/';
    }

    public function getwxStateAttribute()
    {
        return intval($this->wx);
    }
}
