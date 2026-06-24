<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PlugRequest extends FormRequest
{
    public function validationData()
    {
        return $this->post();
    }
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "infoSwitch" => "required|in:0,1",
            "paySwitch" => "required|in:0,1",
            "foreverSwitch" => "required|in:0,1",
            "name" => "required_if:infoSwitch,1|nullable",
            "logo" => "required_if:infoSwitch,1|nullable",
            "payData" => "nullable|array",
            //'smsNum' => "required|integer|min:0",
            "payData.*.day" => "required_if:paySwitch,0|nullable|integer|min:1",
            "payData.*.frontUnit" => "required_if:paySwitch,0|nullable",
            "payData.*.price" => "required_if:paySwitch,0|nullable|numeric|min:0",
            "payData.*.linePrice" => "required_if:paySwitch,0|nullable|numeric|min:0",
            "payData.*.marketingTagSwitch" => "required_if:paySwitch,0|nullable|in:0,1",
            "payData.*.marketingTag" => "nullable",
            "status" => "nullable|integer|in:0,1",
            "payType" => "required|in:0,1",
            "sort" => "nullable|integer",
            "status" => "required|in:0,1"
        ];
    }

    public function attributes()
    {
        return [
            "infoSwitch" => "商家端应用信息",
            "paySwitch" => "应用类型",
            "foreverSwitch" => "设置永久价格",
            "payData.*.day" => "有效时间",
            "payData.*.frontUnit" => "商家端显示单位",
            "payData.*.price" => "售卖价格",
            "payData.*.linePrice" => "划线价",
            "payData.*.marketingTagSwitch" => "营销角标",
            "payData.*.marketingTag" => "角标",
            "payData.*.forever" => "永久价格类型",
            "payType" => "购买方式",
            "status" => "应用状态",
            'smsNum' => "短信赠送"
        ];
    }
}
