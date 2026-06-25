<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'stripe/webhook',
        'calendly/webhook',
        'api/*',
        'chat/log',
        'chat/stream',
        '**/agent_chat/send',
        '**/agent_chat/booking/confirm',
        '**/eligibility_check/assess',
        '**/migration_eligibility/assess',
        'admin/token/grant',
        'admin/token/deduct',
        'admin/student_interests/update',
        //'*/account_article/comment',
        //'*/home/chat',
    ];
}
