<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 生产环境强制使用 HTTPS（解决 asset/url 生成 http 的问题）
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
