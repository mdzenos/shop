<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
use Illuminate\Support\Facades\Session;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class JwtMiddleware extends BaseMiddleware
{
    public function handle($request, Closure $next)
    {
        if (!Session::get('access_token')) {
            return redirect()->route('login')->with('error','Chưa Đăng Nhập!');
        }
        return $next($request);
    }
}
