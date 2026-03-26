<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RegisterCalendlyWebhook extends Command
{
    protected $signature   = 'calendly:register-webhook {--url= : Override the webhook URL (default: APP_URL/calendly/webhook)}';
    protected $description = 'Register the Calendly webhook subscription using CALENDLY_TOKEN and CALENDLY_WEBHOOK_SIGNING_KEY from .env';

    public function handle(): int
    {
        $token      = env('CALENDLY_TOKEN', '');
        $signingKey = env('CALENDLY_WEBHOOK_SIGNING_KEY', '');

        if ($token === '') {
            $this->error('CALENDLY_TOKEN is not set in .env');
            return 1;
        }

        if ($signingKey === '') {
            $this->error('CALENDLY_WEBHOOK_SIGNING_KEY is not set in .env');
            return 1;
        }

        $webhookUrl = $this->option('url') ?: rtrim(env('APP_URL', 'https://ai-mmi.com'), '/') . '/calendly/webhook';
        $this->info("Webhook URL: {$webhookUrl}");

        // Step 1: Get current user to find user URI and organization URI
        $this->info('Fetching Calendly user info...');
        $meResponse = $this->calendlyGet('https://api.calendly.com/users/me', $token);

        if (!$meResponse || empty($meResponse['resource'])) {
            $this->error('Failed to fetch Calendly user. Check your CALENDLY_TOKEN.');
            return 1;
        }

        $userUri = $meResponse['resource']['uri'];
        $orgUri  = $meResponse['resource']['current_organization'];
        $this->line("  User : {$userUri}");
        $this->line("  Org  : {$orgUri}");

        // Step 2: Check if webhook already registered for this URL
        $existing = $this->calendlyGet(
            'https://api.calendly.com/webhook_subscriptions?organization=' . urlencode($orgUri) . '&user=' . urlencode($userUri) . '&scope=user',
            $token
        );

        if ($existing) {
            foreach ($existing['collection'] ?? [] as $sub) {
                if (rtrim($sub['callback_url'], '/') === rtrim($webhookUrl, '/')) {
                    $this->warn("Webhook already registered: {$sub['uri']}");
                    $this->warn('To re-register, delete the existing webhook first via the Calendly dashboard.');
                    return 0;
                }
            }
        }

        // Step 3: Register the webhook
        $this->info('Registering webhook subscription...');
        $body = [
            'url'         => $webhookUrl,
            'events'      => ['invitee.created'],
            'organization'=> $orgUri,
            'user'        => $userUri,
            'scope'       => 'user',
            'signing_key' => $signingKey,
        ];

        $result = $this->calendlyPost('https://api.calendly.com/webhook_subscriptions', $token, $body);

        if (!$result || empty($result['resource']['uri'])) {
            $this->error('Failed to register webhook.');
            if ($result) {
                $this->line(json_encode($result, JSON_PRETTY_PRINT));
            }
            return 1;
        }

        $this->info('Webhook registered successfully!');
        $this->line("  URI    : {$result['resource']['uri']}");
        $this->line("  State  : {$result['resource']['state']}");
        $this->line('');
        $this->info('Make sure these are set in production .env:');
        $this->line("  CALENDLY_TOKEN={$token}");
        $this->line("  CALENDLY_WEBHOOK_SIGNING_KEY={$signingKey}");

        return 0;
    }

    private function calendlyGet(string $url, string $token): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code < 200 || $code >= 300) {
            $this->error("GET {$url} returned HTTP {$code}: {$body}");
            return null;
        }

        return json_decode($body, true);
    }

    private function calendlyPost(string $url, string $token, array $data): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code < 200 || $code >= 300) {
            $this->error("POST {$url} returned HTTP {$code}: {$body}");
            return null;
        }

        return json_decode($body, true);
    }
}
