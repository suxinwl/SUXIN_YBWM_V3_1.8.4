<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoiceMessage extends BaseModel
{
    use HasFactory;
    protected $table = 'voice_message';
    protected $guarded = [];
    protected  $appends = [
        'voiceUrl'
    ];
    protected $hidden = [
        'baseUrl','url'
    ];
    public function getVoiceUrlAttribute(){
        if($this->voiceType ==0){
           return env('APP_URL') . '/storage/default/voice/' . $this->type . ".MP3";
        }else{
            return $this->url;
        }
    }
}
