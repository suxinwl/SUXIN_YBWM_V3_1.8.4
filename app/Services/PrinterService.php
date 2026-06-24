<?php
namespace App\Services;
use App\Models\Printer;
use App\Models\PrinterLog;
use App\Models\TakeoutOrder;
class PrinterService{
    public static function performPrinting($uniacid,$storeId,$orderSn)
    {
        try {
            $printer = Printer::getHardware($uniacid, $storeId);
            //dd($printer);die;
            $order = TakeoutOrder::where('orderSn', $orderSn)->first()->toArray();
            foreach ($printer as $v) {
                switch ($v['type']) {
                    case 1;
                        if ($v['vendor'] =='feie') {
                            $printer_type=1;
                            $content = Printer::feiContents($order);
                            $data =Printer::feiPrint($v, $content,2);
                            $respond=json_decode($data,true);
                            if($respond['msg']=='ok'&&$respond['ret']==0){
                                $respond['msg']='成功';
                            }
                            PrinterLog::registerLog($v,$orderSn,$printer_type,$content,$data,$respond['msg'],$respond['data']);
                        }
                        if ($v['vendor'] =='esLink') {
                            $printer_type=2;
                            $content = Printer::ylyContents($order);
                            $data = Printer::ylyPrint($v, $content);
                            $respond=json_decode($data,true);
                            if($respond['error']=='0'&&$respond['error_description']=='success'){
                                $respond['error_description']='成功';
                            }
                            PrinterLog::registerLog($v,$orderSn,$printer_type,$content,$data,$respond['error_description'],$respond['body']['id']);
                        }
                        break;
                    case 2;
                        $printer_type=3;
                        $content = Printer::labelAllContent($order);
                        $data =Printer::feiPrint($v, $content, 3);
                        $respond=json_decode($data,true);
                        if($respond['msg']=='ok'&&$respond['ret']==0){
                            $respond['msg']='成功';
                        }
                        PrinterLog::registerLog($v,$orderSn,$printer_type,$content,$data,$respond['msg'],$respond['data']);
                        break;
                }
            }

            return true;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
