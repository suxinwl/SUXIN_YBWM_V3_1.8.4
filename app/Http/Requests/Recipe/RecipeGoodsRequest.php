<?php

namespace App\Http\Requests\Recipe;

use Illuminate\Foundation\Http\FormRequest;

class RecipeGoodsRequest extends FormRequest
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
            'changes.*.price' => 'required|numeric|min:0',
        ];
    }

    public function attributes()
    {
        return [
            "price" => "商品价格",
        ];
    }

    public function messages()
    {
        return [
            'changes.*.price.required' => '请填写商品价格',
            'changes.*.price.min' => '商品价格不能小于0',
        ];
    }
}
