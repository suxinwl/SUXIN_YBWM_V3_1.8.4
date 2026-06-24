<?php
namespace App\Models\Print;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class PrintStoreTemplate extends Model
{
    protected $table = 'printer_store_template';
    use HasFactory;
    protected $fillable = ['uniacid','print_type','storeId','data'];
    protected $casts =  [
        'data' => 'array'
    ];
}
