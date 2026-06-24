<?php

namespace App\Http\Requests\Wechat;

use App\Services\ConfigService;
use Config;
use Doctrine\Inflector\Rules\French\Rules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
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
        $appType = Request()->header('appType');
        $uniacid = Request()->header('uniacid');
        $rule =  [
            'nickname' => 'required',
        ];
        /**
         * {
         *   "openid": "OPENID",
         *  "nickname": NICKNAME,
         *  "sex": 1,
         *    "province":"PROVINCE",
         *   "city":"CITY",
         *   "country":"COUNTRY",
         *   "headimgurl":"https://thirdwx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/46",
         *   "privilege":[ "PRIVILEGE1" "PRIVILEGE2"     ],
         *  "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"
         * }
         */
        if ($appType == 'wechat') {
            $rule = [
                'code' => 'required',
                'openid' => 'required',
                'nickname' => 'required',
                'headimgurl' => 'required',
                'unionid' => 'required',
            ];

        }
        if ($appType == 'h5') {
            $rule = [
                'code' => 'required',
                'mobile' => 'required',
            ];
        }
        return $rule;
    }
}
