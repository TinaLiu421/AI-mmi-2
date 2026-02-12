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
        $requestHost = Request::getHost();

        if (app()->environment('local') || in_array($requestHost, ['127.0.0.1', 'localhost'], true)) {
            URL::forceRootUrl(Request::getSchemeAndHttpHost());
            URL::forceScheme(Request::getScheme());
            return;
        }

        $appUrl = config('app.url');
        if (!empty($appUrl)) {
            URL::forceRootUrl(rtrim($appUrl, '/'));
            $scheme = parse_url($appUrl, PHP_URL_SCHEME);
            if ($scheme) {
                URL::forceScheme($scheme);
            }
        }
    }
}