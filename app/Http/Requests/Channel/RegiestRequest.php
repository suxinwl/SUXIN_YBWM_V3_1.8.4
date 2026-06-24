<?php

namespace App\Http\Requests\Channel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegiestRequest extends FormRequest
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
            'mobile' => [
                'required', 'max:11', 'mobile',
                Rule::unique('admins', 'username')->ignore(0)->where(function ($query) {
                    $query->whereNotIn('status', [3]);
                }),
                Rule::unique('admins', 'mobile')->ignore(0)->where(function ($query) {
                    $query->whereNotIn('status', [3]);
                })
            ],
            'nickanme' => "nullable|max:10",
            'password' => 'required|max:32|confirmed',
            'password_confirmation' => 'required|max:32|same:password'
        ];
        return $arr;
    }



    public function message()
    {
        return [
            'mobile:unique' => "该手机号已存在,请重新输入"
        ];
    }
}
