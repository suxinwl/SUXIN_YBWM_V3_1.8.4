<?php

namespace App\Models\Member;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Milon\Barcode\DNS1D;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MemberQrCode extends BaseModel
{
    protected $table = 'member_qrcode';
    use HasFactory;
    protected $fillable = ['uniacid', 'userId', 'oneCode', 'towCode', 'expired', 'qrcode'];
    protected $hidden = ['qrcode'];
    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->qrcode = '98' . GetRandInt(16);
            $model->oneCode = "data:image/png;base64," . DNS1D::getBarcodePNG($model->qrcode, 'C39+', 2, 100);
            $model->towCode = "data:image/png;base64," . base64_encode(QrCode::format('png')->size(400)->generate($model->qrcode));
            $model->expired = date("Y-m-d H:i:s", time() + 30);
        });
    }
}
