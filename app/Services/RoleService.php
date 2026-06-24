<?php

namespace App\Services;

use App\Traits\ResourceTrait;

class RoleService
{
    public static function storeRole()
    {
        return [
            [
                "name" => "首页",
                "role" => "index",
                "children" => [
                    [
                        "name" => "营业统计",
                        "role" => "payMoney"
                    ]
                ]
            ],
            [
                "name" => "订单",
                "role" => "order",
                "children" => [
                    [
                        "name" => "自提订单",
                        "role" => "ziti"
                    ],
                    [
                        "name" => "外送订单",
                        "role" => "waisong"
                    ],
                    [
                        "name" => "店内订单",
                        "role" => "diannei"
                    ],
                    [
                        "name" => "买单订单",
                        "role" => "maidan"
                    ],
                    [
                        "name" => "储值订单",
                        "role" => "chuzhi"
                    ],
                    [
                        "name" => "售后订单",
                        "role" => "shdd"
                    ],
                    [
                        "name" => "积分商城",
                        "role" => "jfsc"
                    ],
                ]
            ],
            [
                "name" => "工作台",
                "role" => "gongzuotai",
                "children" => [
                    [
                        "name" => "线下买单",
                        "role" => "xianxia"
                    ],
                    [
                        "name" => "快速下单",
                        "role" => "kuaisuxiadan"
                    ],
                    [
                        "name" => "桌台点餐",
                        "role" => "zhuotai"
                    ],
                    [
                        "name" => "会员管理",
                        "role" => "huiyuan",
                        "children" => [
                            [
                                "name" => "调整余额",
                                "role" => "tiaozhengyue",
                            ],
                            [
                                "name" => "调整积分",
                                "role" => "tiaozhengjifen",
                            ]
                        ]
                    ],
                    [
                        "name" => "优惠券核销",
                        "role" => "youhuihexiao"
                    ],
                    [
                        "name" => "自提核销",
                        "role" => "zitihexiao"
                    ],
                    [
                        "name" => "叫号取餐",
                        "role" => "jiaohaoqucan"
                    ],
                    [
                        "name" => "商品管理",
                        "role" => "goods",
                        "children" => [
                            [
                                "name" => "库存管理",
                                "role" => "kucun",
                            ],
                            [
                                "name" => "商品上下架",
                                "role" => "shangxiajia",
                            ]
                        ]
                    ],
                    [
                        "name" => "桌位管理",
                        "role" => "zhuoweiguanli"
                    ],
                    [
                        "name" => "活动管理",
                        "role" => "huodongguanli"
                    ],
                    [
                        "name" => "核销记录",
                        "role" => "hexiaojilu"
                    ],
                    [
                        "name" => "评价管理",
                        "role" => "pingjiaguanli"
                    ],
                    [
                        "name" => "数据统计",
                        "role" => "shujutongji"
                    ],
                    [
                        "name" => "门店设置",
                        "role" => "mendianshezhi"
                    ],
                    [
                        "name" => "业务设置",
                        "role" => "yewushezhi"
                    ],
                    [
                        "name" => "配送设置",
                        "role" => "peisongshezhi"
                    ],
                    [
                        "name" => "推广码",
                        "role" => "tuiguangma"
                    ],
                    [
                        "name" => "硬件设备",
                        "role" => "yingjioanshebei"
                    ],
                    [
                        "name" => "门店账户",
                        "role" => "mendianzhanghu"
                    ],
                    [
                        "name" => "手机端设置",
                        "role" => "appshezhi"
                    ],
                    [
                        "name" => "存酒",
                        "role" => "cunjiu"
                    ]
                ]
            ],
            [
                "name" => "我的",
                "role" => "wode",
            ],
        ];
    }


    public static function cashierRole()
    {
        return [
            [
                "name" => "点单",
                "role" => "diandan",
            ],
            [
                "name" => "桌台",
                "role" => "zhuotai",
            ],
            [
                "name" => "叫号",
                "role" => "jiaohao",
            ],
            [
                "name" => "订单",
                "role" => "dingdan",
            ],
            [
                "name" => "会员",
                "role" => "huiyuan",
                "children" => [
                    [
                        "name" => "调整余额",
                        "role" => "tiaozhengyue",
                    ],
                    [
                        "name" => "调整积分",
                        "role" => "tiaozhengjifen",
                    ]
                ]
            ],
            [
                "name" => "对账",
                "role" => "duizhang",
            ],
            [
                "name" => "存酒",
                "role" => "cunjiu",
            ],
            [
                "name" => "商品管理",
                "role" => "goods",
                "children" => [
                    [
                        "name" => "库存管理",
                        "role" => "kucun",
                    ],
                    [
                        "name" => "商品上下架",
                        "role" => "shangxiajia",
                    ]
                ]
            ],
            [
                "name" => "交班记录",
                "role" => "jiaoban",
            ],
            [
                "name" => "硬件管理",
                "role" => "yingjian",
            ],
            [
                "name" => "系统设置",
                "role" => "xitong",
            ]
        ];
    }
}
