<?php
namespace App\Models\Print;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class PrintTemplate extends Model
{
    protected $table = 'printer_template';
    use HasFactory;
    protected $fillable = ['uniacid','print_type', 'data','storeId'];
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }
    protected $casts =  [
        'data' => 'array'
    ];
}
