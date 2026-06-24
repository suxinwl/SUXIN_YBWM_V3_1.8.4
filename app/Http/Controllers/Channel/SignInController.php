<?php

namespace App\Http\Controllers\Channel;

use App\Http\Controllers\Controller;
use App\Models\MemberSignIn\MemberSignIn;
use Illuminate\Http\Request;
use App\Models\SignIn;

class SignInController extends ApiController
{
    public function index(Request $request)
    {
        $row = MemberSignIn::where('uniacid', $this->uniacid())->first();
        return $this->success($row);
    }
}
