<?php
/* *
 * 功能：支付宝手机网站alipay.trade.refund (统一收单交易退款接口)调试入口页面
 * 版本：2.0
 * 修改日期：2016-11-01
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 请确保项目文件有可写权限，不然打印不了日志。
 */

header("Content-type: text/html; charset=utf-8");

Action_Upload();
 function Action_Upload(){
     $furl="C:\\1.jpg";
     $url = 'https://openapi-test.tianquetech.com/merchant/uploadPicture';
     $res =  upload_file_to_cdn($furl, $url);
	 
	// echo $res;

}

 function upload_file_to_cdn($furl,$url){
    //  初始化
    $ch = curl_init();
    // 要上传的本地文件地址"@F:/xampp/php/php.ini"上传时候，上传路径前面要有@符号
     $post_data = array (
         //"file" =>'@D:\a.png',
         "file" =>new CurlFile($furl),
         'orgId'=>'26680846',  //文件类型
         'pictureType'=>'04',
         'reqId'=>'323ds7674354fds32fdsda60174',
     );
    //  设置变量
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);//执行结果是否被返回，0是返回，1是不返回
    curl_setopt($ch, CURLOPT_HEADER, 0);//参数设置，是否显示头部信息，1为显示，0为不显示
    //伪造网页来源地址,伪造来自百度的表单提交
    //表单数据，是正规的表单设置值为非0
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 100);//设置curl执行超时时间最大是多少
    //使用数组提供post数据时，CURL组件大概是为了兼容@filename这种上传文件的写法，
    //默认把content_type设为了multipart/form-data。虽然对于大多数web服务器并
    //没有影响，但是还是有少部分服务器不兼容。本文得出的结论是，在没有需要上传文件的
    //情况下，尽量对post提交的数据进行http_build_query，然后发送出去，能实现更好的兼容性，更小的请求数据包。
     curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
     curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    //   执行并获取结果
     $data = curl_exec($ch);//运行curl
    if($data === FALSE)
    {
        echo "<br/>"," cUrl Error:".curl_error($ch);
    }
    //  释放cURL句柄
    curl_close($ch);
}

?>