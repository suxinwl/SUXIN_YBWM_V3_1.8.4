<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Illuminate\Validation\Rule;

class MemberForm extends FormRequest
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
    public function rules(Request $request)
    {
        return [
            'mobile' => [
                'required', 'max:50',
                Rule::unique('member', 'mobile')->ignore($this->route('mobile'))->where(function ($q) use ($request) {
                    return $q->where("uniacid", $request->header('uniacid'))->where('storeId', $request->header('storeId', 0));
                })
            ],
        ];
    }
}
