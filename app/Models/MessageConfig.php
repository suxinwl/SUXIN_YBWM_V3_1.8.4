<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageConfig extends BaseModel
{
    use HasFactory;
    protected $table = 'message_config';
    protected $guarded = [];

    public function msg()
    {
        return $this->hasOne(ApplyMessage::class, "type", "type");
    }
}
