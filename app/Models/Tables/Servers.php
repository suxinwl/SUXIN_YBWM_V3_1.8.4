<?php

namespace App\Models\Tables;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Servers extends BaseModel
{
    use HasFactory;
    protected $table = 'table_server';
    protected $fillable = [
        'uniacid', 'storeId', 'name', 'sort', 'type', 'price', 'tableId'
    ];
    protected $casts =  [
        'tableId' => 'array',
    ];

    public function tables()
    {
        return $this->belongsToMany(Table::class, 'table_server_tableids', 'serverId', 'tableId');
    }
}
