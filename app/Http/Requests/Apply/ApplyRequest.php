<?php

namespace App\Http\Requests\Apply;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApplyRequest extends FormRequest
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
        if(request()->is("channel/apply") || request()->is("channel/apply/*")){
            $arr = [
                'sort' => 'nullable|numeric',
                'applyImage' => 'required|url',
                'applyName' => [
                    'required', 'max:50',
                    // Rule::unique('apply', 'applyName')->ignore($this->route('apply'))
                ],
                "copyrightSwitch" => "nullable|in:0,1,2",
                "copyright" => 'required_if:copyrightSwitch,1|nullable|array',
                "attachmentType" => "nullable|in:0,1",
                'attachmentData' => 'required_if:attachmentType,1|nullable|array',
                "createUserId" => 'nullable',
                'timeType' => 'nullable|in:1,2',
                // 'day' => 'required_if:timeType,2|nullable|min:1',
                "startTime" => "nullable|required_if:timeType,2|date",
                "endTime" => "nullable|required_if:timeType,2|date",
                'notes' => 'nullable',
                'status' => 'filled|integer|in:1,2',
                'address' => 'required|array'
            ];
        }else if(request()->is("api/apply") || (request()->is("api/apply/*") && request()->method() == 'post')){
            $arr = [
                'sort' => 'nullable|numeric',
                'applyImage' => 'required|url',
                'applyName' => [
                    'required', 'max:50',
                    Rule::unique('apply', 'applyName')->ignore($this->route('apply'))
                ],
                "copyrightSwitch" => "nullable|in:0,1,2",
                "copyright" => 'required_if:copyrightSwitch,1|nullable|array',
                "attachmentType" => "nullable|in:0,1",
                'attachmentData' => 'required_if:attachmentType,1|nullable|array',
                "createUserId" => 'nullable',
                'timeType' => 'required|in:1,2',
                // 'day' => 'required_if:timeType,2|nullable|min:1',
                "startTime" => "nullable|required_if:timeType,2|date",
                "endTime" => "nullable|required_if:timeType,2|date",
                'notes' => 'nullable',
                'status' => 'filled|integer|in:1,2',
                'address' => 'nullable|array'
            ];
        }

        return $arr;
    }

    public function attributes()
    {
        return [];
    }
}
