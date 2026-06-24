<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MusterRequest extends FormRequest
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
            'title' => [
                'required', 'max:30',
                //Rule::unique('setmeal', 'title')->ignore($this->route('muster')),
            ],
            "sort" => 'integer',
            'marketingTagSwitch' => "required|in:0,1",
            "marketingTag" => "nullable",
            "styleSwitch" => "required|in:0,1",
            "day" => "required_if:type,1|nullable|integer",
            "prolongSwitch" => "required_if:type,0|integer|in:0,1",
            "prolong.*.day" => "required_if:prolongSwitch,1|nullable|integer|min:1",
            "prolong.*.frontUnit" => "required_if:prolongSwitch,1",
            "prolong.*.price" => "required_if:required_unless:prolongSwitch,1|nullable|numeric|min:0",
            "prolong.*.marketingTagSwitch" => "required_if:prolongSwitch,1|nullable|in:0,1",
            "prolong.*.marketingTag" => "nullable",
            "soldOutSwitch" => "required|integer",
            "package" => "nullable|array|distinct",
            "money.*.day" => "required_if:type,0|nullable|integer",
            "money.*.frontUnit" => "required_if:type,0",
            "money.*.price" => "required_if:type,0|nullable|numeric|min:0",
            "money.*.marketingTagSwitch" => "required_if:type,0|nullable|in:0,1",
            "money.*.marketingTag" => "nullable",
        ];
    }

    public function attributes()
    {
        return [
            'title' => '套餐名称',
            "sort" => '排序',
            'marketingTagSwitch' => "营销标签",
            "marketingTag" => "标签",
            "styleSwitch" => "展示样式",
            "prolongSwitch" => "套餐续费",
            "prolong.*.day" => "续费有效时间",
            "prolong.*.frontUnit" => "商家端显示单位",
            "prolong.*.price" => "售卖价格",
            "prolong.*.linePrice" => "划线价",
            "prolong.*.marketingTagSwitch" => "营销角标",
            "prolong.*.marketingTag" => "角标",
            "soldOutSwitch" => "soldOutSwitch",
            "package" => "套餐权益",
            "money.*.day" => "套餐价格有效时间",
            "money.*.frontUnit" => "套餐价格商家端显示单位",
            "money.*.price" => "套餐价格售卖价格",
            "money.*.linePrice" => "套餐价格划线价",
            "money.*.marketingTagSwitch" => "套餐价格营销角标",
            "money.*.marketingTag" => "套餐价格角标",
            "type" => "套餐类型"
        ];
    }
}
