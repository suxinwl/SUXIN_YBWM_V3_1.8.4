<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ChangePassword extends FormRequest
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
        $request = Request();
        $arr = [];
        if (!empty($request->old_password) || !empty($request->password_confirmation)) {
            $arr = [
                'password' => 'required||max:32|confirmed',
                'password_confirmation' => 'required|max:32|same:password'
            ];
        }
        return  $arr;
    }
}
