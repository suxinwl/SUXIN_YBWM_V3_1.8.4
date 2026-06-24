<?php

namespace App\Http\Requests\Channel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegionAgentRequest extends FormRequest
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
            'username' => [
                'required', 'max:11', 'mobile',
                Rule::unique('admins', 'username')->ignore($this->route('regionAgent')),
                Rule::unique('admins', 'mobile')->ignore($this->route('regionAgent')),
            ],
            "wxUserId" => "nullable|integer",
            'password' => 'required||max:32|confirmed',
            'password_confirmation' => 'required|max:32|same:password',
            "type" => "required|integer",
            "account" => "nullable|array"
        ];
        if ($this->method() == 'PUT') {
            $arr['password'] = 'nullable';
            $arr['password_confirmation'] = 'required_with:password|nullable|same:password';
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
