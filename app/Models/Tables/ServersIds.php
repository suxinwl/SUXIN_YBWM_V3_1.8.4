<?php

namespace App\Models\Tables;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServersIds extends Model
{
    use HasFactory;
    protected $primaryKey = 'serverId';
    protected $table = 'table_server_tableids';
    protected $fillable = [
        'uniacid', 'storeId', 'serverId', 'tableId'
    ];

    public function tableServer()
    {
       
        return $this->hasOne(Servers::class, 'id', 'serverId');
    }
}
