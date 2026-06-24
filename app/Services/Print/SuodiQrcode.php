<?php

namespace App\Services\Print;
use Illuminate\Database\Eloquent\Collection;
use App\Models\GoodsSku;
use App\Models\GoodsSpu;

class SuodiQrcode
{
    /**
     * 生成奶茶机二维码字符串
     *
     * @param array $goods 商品数组
     * @return string 生成的二维码字符串
     */
    public static function generateNaistr(Collection|array $goods): string
    {
        if ($goods instanceof Collection) {
            $goods = $goods->toArray();
        }

        $naistr = '';
        try {
            foreach ($goods as $rs) {
                $naistr = '';
                $nowgood = GoodsSku::where('specMd5', $rs['specMd5'])->first();
                $currentgood = GoodsSpu::where('id', $nowgood['spuId'])->first();
                $naistr .= $nowgood['sn'];
                $rsAttr = json_encode($rs['attrData'], JSON_UNESCAPED_UNICODE);
                $name = json_encode($currentgood['name'], JSON_UNESCAPED_UNICODE);

                $naistr .= self::getProductCode($name);
                $naistr .= self::getCapacityCode($rsAttr);
                $naistr .= self::getTemperatureCode($rsAttr);
                $naistr .= self::getSugarCode($rsAttr);

                if(strlen($naistr)<19){
                    continue;
                }
                break; // 只生成一个有效的二维码字符串
            }
        } catch (Exception $e) {
            // 可记录异常日志
        }

        return $naistr;
    }

    private static function getProductCode(string $name): string
    {
        $mapping = [
            '葡萄乳酸菌' => 'P0137-',
            '原味乳酸菌' => 'P0138-',
            '冰糖' => 'P0161-',
            '冰糖柠檬水' => 'P0131-',
            '龙井冰鲜茶' => 'P0135-',
            '碧螺春冰鲜茶' => 'P0136-',
            '橙c气泡饮' => 'P0159-',
            '青提气泡饮' => 'P0142-',
            '桂花奇兰轻乳茶' => 'P0158-',
            '龙井轻乳茶' => 'P0126-',
            '长安飘香拿铁' => 'P0117-',
            '碧螺春拿铁' => 'P0120-',
            '拿铁咖啡' => 'P0156-',
            '橙香冰美式' => 'P0139-'
        ];

        foreach ($mapping as $key => $code) {
            if (strstr($name, $key)) {
                return $code;
            }
        }

        return '';
    }

    private static function getCapacityCode(string $rsAttr): string
    {
        $mapping = [
            '1L' => 'C007-',
            '700ml' => 'C006-',
            '500ml' => 'C005-'
        ];

        foreach ($mapping as $key => $code) {
            if (strstr($rsAttr, $key)) {
                return $code;
            }
        }

        return '';
    }

    private static function getTemperatureCode(string $rsAttr): string
    {
        $mapping = [
            '热' => 'T002-',
            '冰' => 'T001-'
        ];

        foreach ($mapping as $key => $code) {
            if (strstr($rsAttr, $key)) {
                return $code;
            }
        }

        return '';
    }

    private static function getSugarCode(string $rsAttr): string
    {
        $mapping = [
            '30g' => 'S010',
            '25g' => 'S009',
            '20g' => 'S008',
            '15g' => 'S007',
            '10g' => 'S006',
            '5g' => 'S006',
            '无糖' => 'S011',
            '三分糖' => 'S004',
            '五分糖' => 'S003',
            '七分糖' => 'S002',
            '标准糖' => 'S001'
        ];

        foreach ($mapping as $key => $code) {
            if (strstr($rsAttr, $key)) {
                return $code;
            }
        }

        return '';
    }
}
