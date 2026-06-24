<?php

namespace App\Http\Requests\Login;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class RetrievePassword extends FormRequest
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
        return true ;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $arr = [
            'phone'=> 'required|exists:admins,mobile',
            'password' => 'required||max:32|confirmed',
            'password_confirmation' => 'required|max:32|same:password'
        ];
        return  $arr;
    }
}
