<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Http;

class RecaptchaRule implements Rule
{
    public function passes($attribute, $value)
    {
        $secret = env('RECAPTCHA_SECRET_KEY');
        $response = Http::post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secret,
            'response' => $value,
            'remoteip' => request()->ip(),
        ]);

        $result = $response->json();

        return $result['success'] ?? false;
    }

    public function message()
    {
        return 'reCAPTCHA verification failed. Please try again.';
    }
}