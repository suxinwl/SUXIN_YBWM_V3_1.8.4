<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class Admin extends FormRequest
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
            'password' => 'required',
            'password_confirmation' => "required|same:password",
            'username' => [
                'required', 'max:11', 'mobile',
                Rule::unique('admins', 'username')->ignore($this->route('admin')),
                Rule::unique('admins', 'mobile')->ignore($this->route('admin')),
            ],
            'createStoreNum' => "required|integer|min:0",
        ];
        if ($this->method() == 'PUT') {
            $arr['password'] = 'nullable';
            $arr['password_confirmation'] = "nullable";
        }
        return $arr;
    }

    public function messages()
    {
        return [
            'username.unique' => '用户帐号已存在',
        ];
    }
}
