<?php

namespace App\Services\AliMini;

use Alipay\EasySDK\Kernel\Config;
use Alipay\EasySDK\Kernel\Factory;
use Alipay\EasySDK\Kernel\Util\ResponseChecker;
use App\Services\BaseService;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class ChannelH5 extends BaseService
{
    public static function getOptions($uniacid)
    {
        $options = new Config();
        $options->protocol = 'https';
        $options->gatewayHost = 'openapi.alipay.com';
        $options->signType = 'RSA2';
        $options->appId = '2021004106666665';
        // 为避免私钥随源码泄露，推荐从文件中读取私钥字符串而不是写入源码中
        $options->merchantPrivateKey = str_replace(PHP_EOL, '', "MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCJJXA3Mkz1mHZnruHOZxp0wabitui2O6FKzIlAobDssJICPuXipttRho9pQizOpMFr3/zzVs16kH7rjUfYN8MkXTWBDoCpRpWCmrHbgxZg8hvEAXww4Df4qsAWu7ujJNm9KgyibzWGUCkhZxjsbW3xFe9U3z1K2n2BMee7fDeWbBQkCDhFHCsE4NedvhiwUXYeHrW9RfSl/xAmKKCiOy9OWXfLy3+sg2i/u3KcpIJSe4vbaIL/K0JwPHFsKNQfXjXzz/Uoubl3S3c7ZbYRLMztqBV4XqNo1rHKBqreT3M7BCoOetz3mh4DVEFAg3d+xRZfYtCOBEsdBXB4OvT6Wu+vAgMBAAECggEARyRQjZFeMpgf87DYGYWKR76cn0ZaeQ19oZtwJ0K40E7XrqqJ/YSoiCXRBrz5GUiFvTu3r1L7y/RgojfraNGkPJzNQGERhL3EmLc+Q6xAU5HDrW1HyuLEpg2NDz3bG8nl2SaS3z/o9/9lFpqwZxRsz36dY91Zohasu/PzTWImQ7SPX1mUAEJEO0/GOlana+Z9i3TCu2WUmQ8toYcGH59sOYmcNKi2CO6y8uMZ56F3ZirTb/+tFo2F4rmBETLH3CBYJthKXVUpCSJoblOfScdFLF+SYNeAgWqcheUX/7+ZE7yqJ3btcVGBJX0HfNWQRs/e5imEFtKNkQZRo2beXdYwAQKBgQDfisWMyLqu2W8OTB6EAgyKroO800kXvINzWPYVIJIbpA2HfjifBIPEcuZpL1Q2JPZpOxcw1fT2uG/gue1tgEbmGuMaHCIcKA/KYXIfsfNg5ClUyulgJM0bv7Vtx65vzkkEBbjpBDm0f2g3Qf+ibuNtgAY3RuXPHREkbD+F7q+drwKBgQCdD0Sh9CfcCJgefFQdK7WnBB2XaRjJCtQmB0Qjavexu+dZ1KFYd0l/9kdPh1AfyCz1XOnBj/79bl5DUxxHF8FIn9eccfQhAx788CaCmheLJ6vDWvEkHRE45to+46q8foy3swZKhGNQu7Cprsa5G1OQq31p9HJWvjsP0lOOGGBOAQKBgQCAoSqhoxOA707vLC/XCBLNbjQR4IZLqUb+ha88YvyP+Strzt6n6gIkdXVOC1TqvwQnc0AZ8tO9cE24Q2RywQCLAeeyK4QZLZhAfSgdQtln5II/726wyxxXHk44uFEQtuPe86f+NVc7HKEMQublQeeOJ7/r6Njt/6zQh5VZ7QmSswKBgGRqd5oITVe70u5i1TCVWp2+3uCIXEVlfAAYUzNF17m2BpODg+jY05BRuIQbYln/lOcHEZkk5IXKA9CNmZ3GjVhOgf7PSKO2qCBVtnl35aedpI7RXS2WvAUia9UboHTFgFiOyG3qOMlMRwYl7X45/3KWgie1F0lodMCH284UvbYBAoGBAJ0ooPE/OABSXGBgtYybs68Qbflk5yKdmwaNFagoQsPgDWeL+mBYkHMV3Ou93z6qzHyjoqrW0jsOgqjTKA/KujRqsZd9dtsZq95Pcz20rXpBwtOwIlzKezf/AqXS7BMMTBBKnsdJjz49fqcSIoiMX2WspEr0XZZ2RUUJmMfI/yvV"); //私钥
        //        $options->alipayCertPath = '<-- 请填写您的支付宝公钥证书文件路径，例如：/foo/alipayCertPublicKey_RSA2.crt -->';
        //        $options->alipayRootCertPath = '<-- 请填写您的支付宝根证书文件路径，例如：/foo/alipayRootCert.crt" -->';
        //        $options->merchantCertPath = '<-- 请填写您的应用公钥证书文件路径，例如：/foo/appCertPublicKey_2019051064521003.crt -->';
        // 注：如果采用非证书模式，则无需赋值上面的三个证书路径，改为赋值如下的支付宝公钥字符串即可
        $options->alipayPublicKey = str_replace(PHP_EOL, '', "-----BEGIN CERTIFICATE-----
        MIIDsjCCApqgAwIBAgIQICIFEDz/IXDj2oTepmw0SzANBgkqhkiG9w0BAQsFADCBgjELMAkGA1UE
        BhMCQ04xFjAUBgNVBAoMDUFudCBGaW5hbmNpYWwxIDAeBgNVBAsMF0NlcnRpZmljYXRpb24gQXV0
        aG9yaXR5MTkwNwYDVQQDDDBBbnQgRmluYW5jaWFsIENlcnRpZmljYXRpb24gQXV0aG9yaXR5IENs
        YXNzIDIgUjEwHhcNMjIwNTEwMDIwMTA0WhcNMjcwNTA5MDIwMTA0WjCBkjELMAkGA1UEBhMCQ04x
        LTArBgNVBAoMJOatpuaxieS6kei0nee9kee7nOenkeaKgOaciemZkOWFrOWPuDEPMA0GA1UECwwG
        QWxpcGF5MUMwQQYDVQQDDDrmlK/ku5jlrp0o5Lit5Zu9Kee9kee7nOaKgOacr+aciemZkOWFrOWP
        uC0yMDg4NjMxMzMzMDM2OTUyMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAjEKdm/Zc
        hS/TmkEim2qrTfSFv4kq44KILM+7NaqsJTzrfZwWAWLeEqCHCM9n2lB/3cU0ImLPpHlzFLoy0hKg
        k+ZtbXhgfTLHtSErQL+WLqsNMe7mcrUYGO/9/51Z8X/b5UJgUzlVWoxnlROCfoGTQCmSmof5RtIn
        WyXWk0CCj3u9jyzXsLhaonB3o1Y5rJEXyrQwCTyAgZlw9UesaKtq+l6J3ry2tHJSKt6tfH4nLA2F
        8CTteSOjmVd6y5RebqRhud6hBlVxoSV7FS7wTLVR3su8rgH6AAydB12IRd+TnGYYk5trNsqF2CLC
        k8sKcktL7OkUFiAHFooqcLqJpqTYCwIDAQABoxIwEDAOBgNVHQ8BAf8EBAMCA/gwDQYJKoZIhvcN
        AQELBQADggEBACjGoUad6nGuvjGmZd3d82ylFg8n7Y7XpyB6r1mNvaTIzGYAnI4e6LbAUs+izMQb
        gsxDjx4gOEKv7iu5JcJWkom5h5hDylR8C8+aUbBx1QgdiKzYbnid+ng/5xc/8Ag7R9oaEwCKbDhi
        UA5EmvW5RPe8b18dlVm5ahRH1NkbzfBjVOSp0c/uAasDR7jP1O9/D16iXL7NfECE0G3YuGN6lBrv
        YOwqmSyXWLyAs0nprkdbHgYueTG2pqLtCZ5jEn/gU2gMtNJKQKzZP7YN6gLrBO13zXLM65qpduey
        PhSBb/EcGmgEU3f0qHQeWHe4AULpZKjMErn80i+2thmzO7Qfi6c=
        -----END CERTIFICATE-----
        -----BEGIN CERTIFICATE-----
        MIIE4jCCAsqgAwIBAgIIYsSr5bKAMl8wDQYJKoZIhvcNAQELBQAwejELMAkGA1UEBhMCQ04xFjAU
        BgNVBAoMDUFudCBGaW5hbmNpYWwxIDAeBgNVBAsMF0NlcnRpZmljYXRpb24gQXV0aG9yaXR5MTEw
        LwYDVQQDDChBbnQgRmluYW5jaWFsIENlcnRpZmljYXRpb24gQXV0aG9yaXR5IFIxMB4XDTE4MDMy
        MjE0MzQxNVoXDTM3MTEyNjE0MzQxNVowgYIxCzAJBgNVBAYTAkNOMRYwFAYDVQQKDA1BbnQgRmlu
        YW5jaWFsMSAwHgYDVQQLDBdDZXJ0aWZpY2F0aW9uIEF1dGhvcml0eTE5MDcGA1UEAwwwQW50IEZp
        bmFuY2lhbCBDZXJ0aWZpY2F0aW9uIEF1dGhvcml0eSBDbGFzcyAyIFIxMIIBIjANBgkqhkiG9w0B
        AQEFAAOCAQ8AMIIBCgKCAQEAsLMfYaoRoPRbmDcAfXPCmKf43pWRN5yTXa/KJWO0l+mrgQvs89bA
        NEvbDUxlkGwycwtwi5DgBuBgVhLliXu+R9CYgr2dXs8D8Hx/gsggDcyGPLmVrDOnL+dyeauheARZ
        fA3du60fwEwwbGcVIpIxPa/4n3IS/ElxQa6DNgqxh8J9Xwh7qMGl0JK9+bALuxf7B541Gr4p0WEN
        G8fhgjBV4w4ut9eQLOoa1eddOUSZcy46Z7allwowwgt7b5VFfx/P1iKJ3LzBMgkCK7GZ2kiLrL7R
        iqV+h482J7hkJD+ardoc6LnrHO/hIZymDxok+VH9fVeUdQa29IZKrIDVj65THQIDAQABo2MwYTAf
        BgNVHSMEGDAWgBRfdLQEwE8HWurlsdsio4dBspzhATAdBgNVHQ4EFgQUSqHkYINtUSAtDPnS8Xoy
        oP9p7qEwDwYDVR0TAQH/BAUwAwEB/zAOBgNVHQ8BAf8EBAMCAQYwDQYJKoZIhvcNAQELBQADggIB
        AIQ8TzFy4bVIVb8+WhHKCkKNPcJe2EZuIcqvRoi727lZTJOfYy/JzLtckyZYfEI8J0lasZ29wkTt
        a1IjSo+a6XdhudU4ONVBrL70U8Kzntplw/6TBNbLFpp7taRALjUgbCOk4EoBMbeCL0GiYYsTS0mw
        7xdySzmGQku4GTyqutIGPQwKxSj9iSFw1FCZqr4VP4tyXzMUgc52SzagA6i7AyLedd3tbS6lnR5B
        L+W9Kx9hwT8L7WANAxQzv/jGldeuSLN8bsTxlOYlsdjmIGu/C9OWblPYGpjQQIRyvs4Cc/mNhrh+
        14EQgwuemIIFDLOgcD+iISoN8CqegelNcJndFw1PDN6LkVoiHz9p7jzsge8RKay/QW6C03KNDpWZ
        EUCgCUdfHfo8xKeR+LL1cfn24HKJmZt8L/aeRZwZ1jwePXFRVtiXELvgJuM/tJDIFj2KD337iV64
        fWcKQ/ydDVGqfDZAdcU4hQdsrPWENwPTQPfVPq2NNLMyIH9+WKx9Ed6/WzeZmIy5ZWpX1TtTolo6
        OJXQFeItMAjHxW/ZSZTok5IS3FuRhExturaInnzjYpx50a6kS34c5+c8hYq7sAtZ/CNLZmBnBCFD
        aMQqT8xFZJ5uolUaSeXxg7JFY1QsYp5RKvj4SjFwCGKJ2+hPPe9UyyltxOidNtxjaknOCeBHytOr
        -----END CERTIFICATE-----
        "); //公钥
        //可设置异步通知接收服务地址（可选）
        //可设置AES密钥，调用AES加解密相关接口时需要（可选）
        return $options;
    }


    public static function setOptions($uniacid)
    {
        Factory::setOptions(self::getOptions($uniacid));
    }

    public static function login($code)
    {
        try {
            $result = Factory::base()->oauth()->getToken($code);
            $responseChecker = new ResponseChecker();
            if ($responseChecker->success($result)) {
                $res = json_decode($result->httpBody, true);
                return $res['alipay_system_oauth_token_response'];
            } else {
                throw new BadRequestException("调用失败，原因：" . $result->msg . "，" . $result->subMsg . PHP_EOL);
            }
        } catch (\Exception $e) {
            throw new BadRequestException($e->getMessage());
        }
    }
}
