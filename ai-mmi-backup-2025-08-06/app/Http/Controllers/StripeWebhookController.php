<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\Subscription;

class StripeWebhookController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret')); 
    }
    
    public function handle(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook.secret');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $secret,
                config('services.stripe.webhook.tolerance', 300)
            );
        } catch (\UnexpectedValueException $e) {
            return response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }

        $type = $event->type;
        Log::info("Stripe event: {$type}");

        switch ($type) {
            case 'checkout.session.completed':
                $this->onCheckoutCompleted($event->data->object);
                break;

            case 'invoice.payment_succeeded':
                $this->onInvoicePaid($event->data->object);
                break;

            case 'invoice.payment_failed':
                $this->onInvoiceFailed($event->data->object);
                break;

            case 'customer.subscription.deleted':
                $this->onSubscriptionCanceled($event->data->object);
                break;

            default:
                // 其它事件按需加
                break;
        }

        return response('OK', 200);
    }

    protected function onCheckoutCompleted($session)
    {
        try {
            $rawClientReference = $session->client_reference_id ?? null;
            [$memberId, $applicationId] = $this->parseClientReference($rawClientReference);

            $subId   = $session->subscription ?? null;
            $cusId   = $session->customer ?? null;
            $sessId  = $session->id ?? null;
            $email   = optional($session->customer_details)->email;

            $priceId = null; $productId = null;
            $amount  = $session->amount_total ?? null;   // 分
            $currency= $session->currency ?? null;

            // 订阅型：从订阅取 price
            if ($subId) {
                $subscription = \Stripe\Subscription::retrieve($subId);
                $item = $subscription->items->data[0] ?? null;
                if ($item) {
                    $priceId   = $item->price->id ?? null;
                    $productId = $item->price->product ?? null;
                }
            } else {
                // 一次性：从 session 的 line_items 取 price
                try {
                    $sess = \Stripe\Checkout\Session::retrieve([
                        'id' => $sessId,
                        'expand' => ['line_items.data.price.product'],
                    ]);
                    $first = $sess->line_items->data[0] ?? null;
                    if ($first) {
                        $price   = $first->price ?? null;
                        $priceId   = $first->price->id ?? null;
                        $productId = $first->price->product ?? null;
                    }
                } catch (\Throwable $e) {
                    Log::warning('fetch session line_items failed: '.$e->getMessage(), ['session_id'=>$sessId]);
                }
            }

            if (!$memberId && $email) {
                $memberId = DB::table('member')->where('email', $email)->value('id');
            }

            // 写 payments
            DB::table('payments')->updateOrInsert(
                ['stripe_session_id' => $sessId],
                [
                    'member_id'              => $memberId,
                    'stripe_customer_id'     => $cusId,
                    'stripe_subscription_id' => $subId,
                    'product_id'             => $productId,
                    'price_id'               => $priceId,
                    'amount_total'           => $amount,
                    'currency'               => $currency,
                    'status'                 => 'paid',
                    'raw_payload'            => json_encode($session->toArray()),
                    'updated_at'             => now(),
                    'created_at'             => now(),
                ]
            );
            if ($memberId && $cusId) {
                DB::table('member')->where('id', $memberId);
                // ->update(['stripe_customer_id' => $cusId]);
            }

            $application = null;
            if ($applicationId) {
                $application = DB::table('course_applications')->where('id', $applicationId)->first();
            } elseif ($memberId) {
                $application = DB::table('course_applications')
                    ->where('member_id', $memberId)
                    ->where('status', 'submitted')
                    ->orderBy('updated_at', 'desc')
                    ->first();
            }

            if ($application) {
                try {
                    DB::table('course_applications')
                        ->where('id', $application->id)
                        ->update([
                            'payment_status'    => 'paid',
                            'payment_reference' => $sessId,
                            'updated_at'        => now(),
                        ]);

                    $application = DB::table('course_applications')->where('id', $application->id)->first();
                    $this->notifyApplicationTeam($application);
                } catch (\Throwable $e) {
                    Log::warning('Failed to update or notify course application payment status', [
                        'application_id' => $application->id ?? $applicationId,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                Log::info('No matching course application found for paid session', [
                    'client_reference' => $rawClientReference,
                    'member_id' => $memberId,
                ]);
            }

            // 订阅创建（双重日志→定位）
            Log::info('subs:probe.vars', [
            'sessId'=>$sessId,'memberId'=>$memberId,'priceId'=>$priceId,'subId'=>$subId,'cusId'=>$cusId
            ]);
            Log::info('subs:probe.plan', [
            'priceId'=>$priceId,
            'plan_found'=> (bool) DB::table('plans')->where('stripe_price_id',$priceId)->exists()
            ]);

            if ($memberId && $priceId) {
                $plan = DB::table('plans')->where('stripe_price_id', $priceId)->first();
                if ($plan) {
                    $start = now();
                    $end   = $plan->duration_months ? now()->copy()->addMonths($plan->duration_months) : null;

                    // ---------- 幂等检查 ----------
                    $duplicate = false;
                    if ($subId) {
                        // 订阅型：同一个 stripe_subscription_id 视为同一单
                        $duplicate = DB::table('subscriptions')
                            ->where('stripe_subscription_id', $subId)
                            ->exists();
                    } else {
                        // 一次性：同一 session_id + 同一 plan 视为同一单
                        $duplicate = DB::table('subscriptions')
                            ->where('member_id', $memberId)
                            ->where('plan_id', $plan->id)
                            ->whereJsonContains('meta->session_id', $sessId)
                            ->exists();
                    }

                    if ($duplicate) {
                        Log::info('subs:skip duplicate', ['plan_code'=>$plan->code,'subId'=>$subId,'session'=>$sessId]);
                        return;
                    }

                    // ---------- 覆盖策略 ----------
                    if ($plan->business_domain === 'migration') {
                        // 仅对 migration 类做覆盖：把该会员已有的 migration 有效订阅标记为 expired
                        DB::table('subscriptions')
                            ->where('member_id', $memberId)
                            ->where('status', 'active')
                            ->where(function($q){
                                $q->whereNull('ends_at')->orWhere('ends_at','>', now());
                            })
                            ->whereIn('plan_id', function($q){
                                $q->select('id')->from('plans')->where('business_domain','migration');
                            })
                            ->update(['status'=>'expired','updated_at'=>now()]);
                    }
                    
                    // education（application）不做任何覆盖，直接并行

                    // ---------- 永远插入（不再用“整会员唯一”的 update） ----------
                    Log::info('subs:probe.before_insert', ['plan_code'=>$plan->code]);

                    $id = DB::table('subscriptions')->insertGetId([
                        'member_id'              => $memberId,
                        'plan_id'                => $plan->id,
                        'status'                 => 'active',
                        'started_at'             => $start,
                        'ends_at'                => $end,
                        'currency'               => strtoupper($currency ?? 'USD'),
                        'amount_usd'             => is_null($amount) ? 0 : $amount/100,
                        'stripe_customer_id'     => $cusId,
                        'stripe_subscription_id' => $subId, // 一次性可能为 null
                        // 为一次性场景保留 session_id，方便幂等判断
                        'meta'                   => json_encode(['session_id'=>$sessId]),
                        'created_at'             => now(),
                        'updated_at'             => now(),
                    ]);

                    Log::info('subs:done', ['action'=>'insert','id'=>$id,'plan_code'=>$plan->code]);
                } else {
                    Log::warning('price未映射到plan', ['price_id'=>$priceId]);
                }
            } else {
                Log::warning('skip subscriptions create', ['memberId'=>$memberId,'priceId'=>$priceId,'session_id'=>$sessId]);
            }

        } catch (\Throwable $e) {
            Log::error('onCheckoutCompleted error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
        }
    }


    protected function onInvoicePaid($invoice)
    {
        try {
            $subId   = $invoice->subscription;
            $priceId = null;

            $line = $invoice->lines->data[0] ?? null;
            if ($line) {
                $priceId = $line->price->id ?? null;
            }

            // 找 memberId
            $memberId = DB::table('payments')
                ->where('stripe_subscription_id', $subId)
                ->whereNotNull('member_id')
                ->orderByDesc('id')
                ->value('member_id');

            if (!$memberId && $invoice->customer_email) {
                $memberId = DB::table('member')
                    ->where('email', $invoice->customer_email)
                    ->value('id');
            }

            // 更新 payments
            DB::table('payments')
                ->where('stripe_subscription_id', $subId)
                ->update([
                    'status'      => 'paid',
                    'raw_payload' => json_encode($invoice),
                    'updated_at'  => now(),
                    'member_id'   => $memberId,
                ]);

            if ($memberId && $priceId) {
                $plan = DB::table('plans')->where('stripe_price_id', $priceId)->first();
                if ($plan) {
                    $start = now();
                    $end   = $plan->duration_months ? now()->copy()->addMonths($plan->duration_months) : null;

                    // Find existing subscription row (idempotency + renewal)
                    $existing = DB::table('subscriptions')
                        ->where('stripe_subscription_id', $subId)
                        ->orderByDesc('id')
                        ->first();

                    if ($existing) {
                        // Renewal: extend ends_at so access continues after the new billing period.
                        // Also expire any OTHER active migration subscriptions (plan switch scenario).
                        if ($plan->business_domain === 'migration') {
                            DB::table('subscriptions')
                                ->where('member_id', $memberId)
                                ->where('status', 'active')
                                ->where('id', '!=', $existing->id)
                                ->whereIn('plan_id', function($q){
                                    $q->select('id')->from('plans')->where('business_domain','migration');
                                })
                                ->update(['status'=>'expired','updated_at'=>now()]);
                        }
                        DB::table('subscriptions')
                            ->where('id', $existing->id)
                            ->update(['status'=>'active','ends_at'=>$end,'updated_at'=>now()]);
                        Log::info('invoice.paid → renewal extended ends_at', [
                            'sub_id'    => $existing->id,
                            'plan_code' => $plan->code,
                            'ends_at'   => $end,
                        ]);
                    } else {
                        // No row yet (checkout.session.completed may not have fired yet).
                        if ($plan->business_domain === 'migration') {
                            DB::table('subscriptions')
                                ->where('member_id', $memberId)
                                ->where('status', 'active')
                                ->whereIn('plan_id', function($q){
                                    $q->select('id')->from('plans')->where('business_domain','migration');
                                })
                                ->update(['status'=>'expired','updated_at'=>now()]);
                        }
                        $id = DB::table('subscriptions')->insertGetId([
                            'member_id'              => $memberId,
                            'plan_id'                => $plan->id,
                            'status'                 => 'active',
                            'started_at'             => $start,
                            'ends_at'                => $end,
                            'currency'               => strtoupper($invoice->currency ?? 'USD'),
                            'amount_usd'             => is_null($invoice->amount_paid) ? 0 : $invoice->amount_paid/100,
                            'stripe_customer_id'     => $invoice->customer,
                            'stripe_subscription_id' => $subId,
                            'meta'                   => json_encode(['invoice_id'=>$invoice->id]),
                            'created_at'             => now(),
                            'updated_at'             => now(),
                        ]);
                        Log::info('invoice.paid → subscriptions insert', ['id'=>$id,'plan_code'=>$plan->code]);
                    }
                } else {
                    Log::warning('invoice.paid price not mapped to plan', ['price_id'=>$priceId]);
                }
            } else {
                Log::warning('invoice.paid missing member or price, skipped', [
                    'member_id'=>$memberId,
                    'price_id'=>$priceId
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('onInvoicePaid error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
        }
    }



    protected function onInvoiceFailed($invoice)
    {
        $subId = $invoice->subscription;

        DB::table('payments')
            ->where('stripe_subscription_id', $subId)
            ->update([
                'status'      => 'failed',
                'raw_payload' => json_encode($invoice),
                'updated_at'  => now(),
            ]);
    }

    protected function onSubscriptionCanceled($subscription)
    {
        $subId = $subscription->id;

        DB::table('payments')
            ->where('stripe_subscription_id', $subId)
            ->update([
                'status'      => 'canceled',
                'raw_payload' => json_encode($subscription),
                'updated_at'  => now(),
            ]);

        // Revoke access immediately: mark subscription record as canceled
        // so member.active_subscriptions no longer includes this plan.
        $affected = DB::table('subscriptions')
            ->where('stripe_subscription_id', $subId)
            ->where('status', 'active')
            ->update([
                'status'     => 'canceled',
                'ends_at'    => now(),
                'updated_at' => now(),
            ]);

        Log::info('subscription.canceled → access revoked', [
            'stripe_sub_id' => $subId,
            'rows_updated'  => $affected,
        ]);
    }

    public function paySuccess(Request $request)
    {
        $sessionId = $request->query('session_id');
        $memberId  = auth()->id();   // 登录会员就是 members.id

        if ($sessionId && $memberId) {
            DB::table('payments')
                ->where('stripe_session_id', $sessionId)
                ->update(['member_id' => $memberId, 'updated_at' => now()]);
        }

        return view('web.pay_success');
    }

    protected function parseClientReference(?string $reference): array
    {
        $memberId = null;
        $applicationId = null;

        if (empty($reference)) {
            return [$memberId, $applicationId];
        }

        if (preg_match('/^(\d+)\|APP\-([0-9]+)$/', $reference, $matches)) {
            $memberId = (int) $matches[1];
            $applicationId = (int) $matches[2];
            return [$memberId, $applicationId];
        }

        if (is_numeric($reference)) {
            $memberId = (int) $reference;
        }

        return [$memberId, $applicationId];
    }

    protected function notifyApplicationTeam($application): void
    {
        try {
            $documents = json_decode($application->document_paths ?? '[]', true) ?: [];
            $englishTests = json_decode($application->english_tests ?? '[]', true) ?: [];
            $scholarships = json_decode($application->scholarship_colleges ?? '[]', true) ?: [];

            $summary = [
                'Applicant name'       => trim(($application->given_name ?? '') . ' ' . ($application->family_name ?? '')),
                'Email'                => $application->email_address ?? '-',
                'Mobile'               => $application->mobile_number ?? '-',
                'Date of birth'        => $application->date_of_birth ?? '-',
                'Nationality'          => $application->nationality ?? '-',
                'Highest education'    => $application->highest_education ?? '-',
                'Has English test'     => ($application->has_english_test ? 'Yes' : 'No'),
                'English tests'        => $this->formatKeyValueList($englishTests),
                'Financial support'    => ($application->has_financial_support ? 'Yes' : 'No'),
                'Financial notes'      => $application->financial_notes ?? '-',
                'Target institution'   => $application->target_institution ?? '-',
                'Target program'       => $application->target_program ?? '-',
                'Preferred start year' => $application->start_year ?? '-',
                'Wants scholarship'    => ($application->wants_scholarship ? 'Yes' : 'No'),
                'Scholarship colleges' => empty($scholarships) ? '-' : implode(', ', $scholarships),
                'Residential address'  => nl2br(e($application->residential_address ?? '-')),
            ];

            $rows = '';
            foreach ($summary as $label => $value) {
                $rows .= '<tr><th align="left" style="padding:6px 10px;background:#f4f6fb;border:1px solid #dfe4ef;">'
                    . e($label)
                    . '</th><td style="padding:6px 10px;border:1px solid #dfe4ef;">'
                    . (is_string($value) ? $value : e((string) $value))
                    . '</td></tr>';
            }

            $docList = '';
            foreach ($documents as $doc) {
                $docList .= '<li>'
                    . e($doc['label'] ?? $doc['original_name'] ?? 'Document')
                    . ' - ' . e($doc['original_name'] ?? basename($doc['path'] ?? ''))
                    . '</li>';
            }
            if (!$docList) {
                $docList = '<li>No documents uploaded.</li>';
            }

            $html = '<h2>New paid course application</h2>'
                . '<p>Payment reference: <strong>' . e($application->payment_reference ?? 'N/A') . '</strong></p>'
                . '<table cellspacing="0" cellpadding="0" style="border-collapse:collapse;width:100%;font-family:Arial,Helvetica,sans-serif;font-size:14px;">'
                . $rows
                . '</table>'
                . '<h3 style="margin-top:16px;">Documents</h3>'
                . '<ul>' . $docList . '</ul>';

            $attachments = [];
            foreach ($documents as $doc) {
                $filePath = !empty($doc['path']) ? public_path($doc['path']) : null;
                if ($filePath && file_exists($filePath)) {
                    $attachments[] = [
                        'path' => $filePath,
                        'name' => $doc['original_name'] ?? basename($filePath),
                    ];
                }
            }

            $subjectName = trim(($application->given_name ?? '') . ' ' . ($application->family_name ?? ''));
            $subject = 'New Paid Course Application - ' . ($subjectName ?: ($application->email_address ?? 'Applicant'));
            Log::info('Preparing course application email', [
                'application_id' => $application->id,
                'subject'        => $subject,
                'attachments'    => count($attachments),
            ]);
            $this->sendCourseApplicationEmail($subject, $html, $attachments);
        } catch (\Throwable $e) {
            Log::error('Failed to notify application team: ' . $e->getMessage());
        }
    }

    private function formatKeyValueList(array $items): string
    {
        if (empty($items)) {
            return '-';
        }
        $parts = [];
        foreach ($items as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $label = is_string($key) ? strtoupper($key) : ('Test ' . ($key + 1));
            $parts[] = $label . ': ' . $value;
        }
        return empty($parts) ? '-' : implode(', ', $parts);
    }

    private function sendCourseApplicationEmail(string $subject, string $html, array $attachments = []): void
    {
        try {
            require_once app_path('Libraries/sendgrid/sendgrid-php.php');
            $email = new \SendGrid\Mail\Mail();
            $fromAddress = env('MAIL_FROM_ADDRESS', 'no-reply@at-creative.com') ?: 'no-reply@at-creative.com';
            $fromName = env('MAIL_FROM_NAME', 'AI-mmi') ?: 'AI-mmi';
            $email->setFrom($fromAddress, $fromName);
            $email->setSubject($subject);
            $email->addTo('info@ai-mmi.com');
            $email->addContent('text/html', $html);

            foreach ($attachments as $file) {
                $contents = @file_get_contents($file['path']);
                if ($contents === false) {
                    continue;
                }
                $mime = mime_content_type($file['path']) ?: 'application/octet-stream';
                $email->addAttachment(
                    base64_encode($contents),
                    $mime,
                    $file['name'],
                    'attachment'
                );
            }

            $apiKey = getenv('SENDGRID_API_KEY');
            if (empty($apiKey)) {
                Log::error('SendGrid API key missing; cannot deliver course application email');
                return;
            }

            $sendgrid = new \SendGrid($apiKey);
            $response = $sendgrid->send($email);
            if (method_exists($response, 'statusCode') && $response->statusCode() >= 400) {
                Log::error('SendGrid API responded with error', [
                    'status' => $response->statusCode(),
                    'body'   => method_exists($response, 'body') ? $response->body() : null,
                ]);
            } else {
                Log::info('Course application email dispatched via SendGrid', [
                    'status' => method_exists($response, 'statusCode') ? $response->statusCode() : null,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('SendGrid delivery failed: ' . $e->getMessage());
        }
    }

}

class SubscriptionController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function cancel(Request $request, $id)
    {
        $memberId = auth()->id();

        // 找用户的订阅
        $subscription = DB::table('subscriptions')
            ->where('id', $id)
            ->where('member_id', $memberId)
            ->where('status', 'active')
            ->first();

        if (!$subscription) {
            return back()->with('error', '未找到有效的订阅。');
        }

        try {
            if ($subscription->stripe_subscription_id) {
                // 调用 Stripe API → 设置取消
                Subscription::update($subscription->stripe_subscription_id, [
                    'cancel_at_period_end' => true, // 当前周期后取消
                ]);
            }

            // 更新数据库
            DB::table('subscriptions')->where('id', $subscription->id)->update([
                'status'     => 'canceled',
                'updated_at' => now(),
            ]);

            return back()->with('success', '订阅已成功取消');
        } catch (\Throwable $e) {
            Log::error('取消订阅失败: '.$e->getMessage());
            return back()->with('error', '取消失败，请稍后再试。');
        }
    }
}
