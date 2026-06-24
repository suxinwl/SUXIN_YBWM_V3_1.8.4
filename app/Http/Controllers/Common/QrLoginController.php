<?php

namespace App\Http\Controllers\Common;

use App\Models\Region;
use App\Traits\HelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller as BaseController;
use App\Models\ShortLink;
use App\Services\OpenWechat\ChannelOpenWechat;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class QrLoginController extends ApiController
{
    use HelperTrait;

    public function qrCode()
    {
        $requestId =  wxNonceStr(32);
        $url = json_encode(['type' => "qrLogin", 'requestId' => $requestId]);
        $url = "data:image/png;base64," . base64_encode(QrCode::format('png')->size(400)->generate($url));
        return $this->success([
            'requestId' => $requestId,
            'qrCode' => $url
        ]);
    }
    

    public function store(Request $request)
    {
        Cache::set($request->requestId, $request->data);
        return $this->success([], '扫码登录成功');
    }

    public function show(Request $request, $requestId)
    {
        $data = Cache::get($requestId);
        return $this->success($data);
    }
}
