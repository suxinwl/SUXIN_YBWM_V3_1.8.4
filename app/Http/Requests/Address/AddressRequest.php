<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddressRequest extends FormRequest
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
        $arr = [
            'address' => 'required',
            'contact' => "required",
            "lat" => "required",
            "lng" => "required",
            "mobile" => "required",
            "call" => "required",
            "label" => "required",
            "isDefault" => "required"
        ];
        return $arr;
    }

    public function messages()
    {
        return [
            'address' => '地址',
            'contact' => '联系人',
            'lat' => 'lat',
            'lng'=>'lng',
            'mobile'=>"手机号",
            'call'=>"称呼",
            "label"=>"标签",
            "isDefault"=>"默认"
        ];
    }
}
