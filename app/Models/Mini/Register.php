<?php

namespace App\Models\Mini;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Register extends Model
{
    protected $table = 'register_mini';
    protected $fillable = ['legal_persona_idcard','uniacid', 'type', 'appName', 'openid', 'code', 'name', 'code_type', 'legal_persona_wechat', 'legal_persona_name', 'component_phone', 'status', 'auth_code', 'appid', 'msg', 'state'];
    protected $appends = [
        'stateFormat'
    ];
    protected $attributes = [
        'type' => 1
    ];
    public function getPostDataAttribute()
    {
        if ($this->type == 1) {
            return [
                'code' => $this->code,
                'name' => $this->name,
                'code_type' => $this->code_type,
                'legal_persona_wechat' => $this->legal_persona_wechat,
                'legal_persona_name' => $this->legal_persona_name,
                'component_phone' => $this->component_phone,
            ];
        }
        if ($this->type == 2) {
            return ['verify_info' => [
                'code' => $this->code,
                'enterprise_name' => $this->name,
                'code_type' => $this->code_type,
                'legal_persona_wechat' => $this->legal_persona_wechat,
                'legal_persona_name' => $this->legal_persona_name,
                'component_phone' => $this->component_phone,
                'legal_persona_idcard'=>$this->legal_persona_idcard
            ]];
        }
    }


    public function getStateFormatAttribute()
    {
        $data = [
            0 => '审核中',
            1 => '申请成功',
            2 => "审核失败"
        ];
        return $data[$this->state];
    }
}
