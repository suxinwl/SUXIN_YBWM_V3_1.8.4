<?php

namespace App\Http\Requests\Withdrawal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class WithdrawalConfig extends FormRequest
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
            'type' => "required|in:1,2,3",
            'userId' => "required_if:type,1|nullable|integer",
            'realName' => "required_if:type,1|nullable",
            'openid' => "required_if:type,1|nullable",
            "realName" => "required_if:type,1|nullable",
            "mobile" => "required_if:type,1|nullable",
            "wxSkm" => "required_if:type,1|nullable",
            "aliUser" => "required_if:type,2|nullable",
            "aliSkm" => "required_if:type,2|nullable",
            'aliRealName' => "required_if:type,2|nullable",
            'aliMobile' => "required_if:type,2|nullable",
            "bankName" => "required_if:type,3|nullable",
            "bankCard" => "required_if:type,3|nullable",
            "accountBank" => "required_if:type,3|nullable",
            "bankRealName" => "required_if:type,3|nullable",
            "bankMobile" => "required_if:type,3|nullable",
        ];
    }

    public function attributes()
    {
        return [
            'type' => "类型",
            'userId' => "关联微信用户",
            "realName" => "真实姓名",
            "mobile" => "手机号",
            "wxSkm" => "微信收款码",
            "aliUser" => "支付宝账号",
            "aliSkm" => "支付宝收款码",
            "aliNickNnme" => "真实姓名",
            "aliMobile" => "手机号",
            "bankName" => "所属银行",
            "bankCard" => "银行卡号",
            "accountBank" => "开户行",
            "bankRealName" => "真实姓名",
            "bankMobile" => "手机号",
        ];
    }
}
