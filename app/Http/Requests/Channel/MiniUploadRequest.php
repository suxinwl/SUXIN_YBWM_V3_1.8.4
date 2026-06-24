<?php

namespace App\Http\Requests\Channel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MiniUploadRequest extends FormRequest
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
            'version' => 'required|max:100',
            'desc'=>"required|max:200",
            'plugin'=>"array"
        ];
    }
}
