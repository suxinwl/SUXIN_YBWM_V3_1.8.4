<?php

namespace App\Http\Requests\Goods;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class GoodsRequest extends FormRequest
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
            "sort" => "required|integer|min:0",
            "type" => "required|integer",
            "name" => "required|between:1,50",
            'labelId' => "nullable|array",
            'catId' => "required|array",
            'markId' => "nullable|integer",
            "pinYin" => "nullable|between:0,20",
            "initialSales" => "nullable|integer",
            "sales" => "required|integer",
            "unitId" => "nullable|integer",
            "logo" => "required",
            "channelIds" => "array|required",
            "cover" => "nullable",
            "video" => "nullable",
            "images" => "nullable|array",
            "isShow" => "required|integer|in:0,1",
            'vipPriceSwitch' => "required_unless:isShow,0|integer|in:0,1",
            'specSwitch' => "required_unless:isShow,1|integer|in:0,1",
            'specData' => "nullable|required_if:specSwitch,1|array",
            "attrSwitch" => "required_unless:isShow,1|integer|in:0,1",
            "attrData" => "nullable|required_if:attrSwitch,1|array",
            "materialSwitch" => "required_unless:isShow,1|required|integer|in:0,1",
            "materialData" => "nullable|required_if:materialSwitch,1|array",
            "salesTimeSwitch" => "required_unless:isShow,1|integer|in:0,1",
            "salesTimeData" => "nullable|required_if:salesTimeSwitch,1|array",
            "salesTimeData.week" => "nullable|required_if:salesTimeSwitch,1|array",
            "salesTimeData.times" => "nullable|required_if:salesTimeSwitch,1|array",
            "salesTimeData.times.*.start" => "nullable|required_if:salesTimeSwitch,1",
            "salesTimeData.times.*.end" => "nullable|required_if:salesTimeSwitch,1",
            'salesType' => "required_unless:isShow,1|integer",
            'min' => "nullable|required_if:salesType,1|integer|min:1",
            "orderlimitSwitch" => "required_unless:isShow,1|integer|in:0,1",
            "orderlimit" => "nullable|required_if:orderlimitSwitch,1|integer|min:0",
            "userlimitSwitch" => "required_unless:isShow,1|integer|in:0,1",
            "userlimit" => "nullable|required_if:userlimitSwitch,1|integer|min:0",
            "daylimitSwitch" => "required_unless:isShow,1|integer|in:0,1",
            "daylimit" => "nullable|required_if:daylimitSwitch,1|integer|min:0",
            "oneDeliverySwitch" => "required_unless:isShow,1|integer|in:0,1",
            "shareTitle" => "nullable",
            "shareImage" => "nullable",
            "shareNotes" => "nullable",
            "singleSpec" => "nullable|array|required_if:specSwitch,0",
            "singleSpec.price" => "nullable|required_if:specSwitch,0|numeric|min:0",
            "singleSpec.boxMoney" => "nullable|required_if:specSwitch,0|numeric|min:0",
            "singleSpec.linePrice" => "nullable|required_if:specSwitch,0|numeric|min:0",
            "singleSpec.costPrice" => "nullable|required_if:specSwitch,0|numeric|min:0",
            "singleSpec.inventory" => "nullable|required_if:specSwitch,0|integer|min:0",
            "singleSpec.component" => "nullable|integer|min:0",
            "singleSpec.dayFilling" => "nullable|required_if:specSwitch,0|integer|in:0,1",
            "singleSpec.barcode" => "nullable",
            "singleSpec.sn" => "nullable",
            "skus" => "nullable|required_if:specSwitch,1|array",
            "skus.*.specName" => "nullable||required_if:specSwitch,1|array",
            "skus.*.price" => "nullable|required_if:specSwitch,1|numeric|min:0",
            "skus.*.boxMoney" => "nullable|required_if:specSwitch,1|numeric|min:0",
            "skus.*.linePrice" => "nullable|required_if:specSwitch,1|numeric|min:0",
            "skus.*.costPrice" => "nullable|required_if:specSwitch,1|numeric|min:0",
            "skus.*.inventory" => "nullable|required_if:specSwitch,1salesType|integer|min:0",
            "skus.*.component" => "nullable|integer|min:0",
            "skus.*.dayFilling" => "nullable|required_if:specSwitch,1|integer|in:0,1",
            "skus.*.barcode" => "nullable",
            "skus.*.sn" => "nullable"
        ];
    }

    public function attributes()
    {
        return [
            "sort" => "排序",
            "type" => "商品类型",
            "name" => "商品名称",
            'labelId' => "商品标签",
            'catId' => "商品分类",
            'markId' => "商品角标",
            "pinYin" => "拼音助记码",
            "initialSales" => "初始销量",
            "sales" => "实际销量",
            "unitId" => "单位",
            "logo" => "商品主图",
            "cover" => "封面",
            "video" => "视频",
            "images" => "详情图",
            "channelIds" => "商品渠道",
            "isShow" => "是否为展示商品",
            'specSwitch' => "规格",
            'specData' => "规格数据",
            "attrSwitch" => "属性",
            "attrData" => "属性数据",
            "materialSwitch" => "加料",
            "materialData" => "加料数据",
            'vipPriceSwitch' => '是否参与会员折扣',
            "salesTimeSwitch" => "时段销售",
            "salesTimeData" => "时段销售",
            "salesTimeData.week" => "营业时间",
            "salesTimeData.times" => "营业时间",
            "salesTimeData.times.*.start" => "供应开始时间",
            "salesTimeData.times.*.end" => "供应结束时间",
            'salesType' => "售卖方式",
            'min' => '最少购买份数',
            "orderlimitSwitch" => "每单限购",
            "orderlimit" => "每单限购",
            "userlimitSwitch" => "每人限购",
            "userlimit" => "每人限购",
            "daylimitSwitch" => "每天限购",
            "daylimit" => "每天限购",
            "oneDeliverySwitch" => "单点不送",
            "shareTitle" => "分享标题",
            "shareImage" => "分享图片",
            "shareNotes" => "分享备注",
            "skus" => "规格商品",
            "skus.*.specName" => "规格名称",
            "skus.*.type" => "规格类型",
            "skus.*.price" => "销售价",
            "skus.*.linePrice" => "划线价",
            "skus.*.costPrice" => "成本价",
            "skus.*.inventory" => "库存",
            "skus.*.component" => "分量",
            "skus.*.dayFilling" => "次日补齐",
            "skus.*.barcode" => "条码",
            "skus.*.sn" => "编码",
            "singleSpec.specName" => "规格名称",
            "singleSpec.type" => "规格类型",
            "singleSpec.price" => "销售价",
            "singleSpec.linePrice" => "划线价",
            "singleSpec.costPrice" => "成本价",
            "singleSpec.inventory" => "库存",
            "singleSpec.component" => "分量",
            "singleSpec.dayFilling" => "次日补齐",
            "singleSpec.barcode" => "条码",
            "singleSpec.sn" => "编码",
        ];
    }
}
