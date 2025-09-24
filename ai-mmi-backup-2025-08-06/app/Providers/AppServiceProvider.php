<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Request;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // 1) 优先使用 .env 的 APP_URL 作为根（不会带上 /en 等路由前缀）
        $appUrl = config('app.url'); // .env: APP_URL=http://127.0.0.1:8000

        if (!empty($appUrl)) {
            URL::forceRootUrl(rtrim($appUrl, '/'));                 // 统一根
            $scheme = parse_url($appUrl, PHP_URL_SCHEME);           // http / https
            if ($scheme) {
                URL::forceScheme($scheme);                          // 统一协议
            }
        } else {
            // 2) 兜底：用当前请求的 scheme+host（不含路径）
            URL::forceRootUrl(Request::getSchemeAndHttpHost());
        }

        // ✅ 不再手写拼接域名/端口，不再 view()->share 自己的 app_url 变量
        //    统一交给 Laravel 的 URL/asset 生成器处理
    }
}
