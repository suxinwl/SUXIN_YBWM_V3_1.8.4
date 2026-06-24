<?php
namespace App\Http\Requests\Channel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class ShopAccountRequest extends FormRequest
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
//        return [
//            'max_take_percentage' => 'required|gt:min_take_percentage',
//            'max_withdrawal_expense' => 'required|gt:min_withdrawal_expense'
//        ];
    }
}
