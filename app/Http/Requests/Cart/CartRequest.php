<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CartRequest extends FormRequest
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
            "storeId" => "required|integer|min:0",
            "num" => "required|integer",
            "specMd5" => "required",
            "attrData" => "nullable|array",
            'materialData' => "nullable|array",
            "materialData.*.id" => "nullable|required_with:materialData|integer",
            "materialData.*.num" => "nullable|required_with:materialData|integer",
            "materialData.*.price" => "nullable|required_with:materialData",
        ];
    }

    public function attributes()
    {
        return [
            "storeId" => "门店id",
            "specMd5" => "商品",
            "spuId" => "商品id",
            "attrData" => "商品描述",
            "materialData" => "加料",
            "materialData.*.id" => "加料商品id",
            "materialData.*.num" => "加料商品数量",
            "materialData.*.price" => "加料商品单价"
        ];
    }
}
