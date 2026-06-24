<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

class StoreGoodsRequest extends FormRequest
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
            "changes" => "required|array",
            'changes.*.specMd5' => 'required',
            'changes.*.inventory' => 'required|integer|min:0',
            'changes.*.dayFilling' => 'required|integer|min:0',
            'changes.*.surplusInventory' => 'required|integer|min:0',
        ];
    }

    public function attributes()
    {
        return [
            "inventory" => "库存",
            "surplusInventory"=>"剩余库存",
            'dayFilling'=>"次日置满",
            'specMd5'=>"商品"
        ];
    }

    public function messages()
    {
        return [
            'changes.*.surplusInventory.required' => '请填剩余库存',
            'changes.*.surplusInventory.min' => '剩余库存不能小于0',
        ];
    }
}
