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
        Stripe::setApiKey(config('services.stripe.secret')); // ← 关键
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
            // 1) 把 member_id 拿出来
            $memberId = $session->client_reference_id ?? null;

            $subId  = $session->subscription ?? null;
            $cusId  = $session->customer ?? null;
            $sessId = $session->id ?? null;
            $email  = optional($session->customer_details)->email;

            $priceId = null; $productId = null;
            $amount  = $session->amount_total ?? null;
            $currency= $session->currency ?? null;

            if ($subId) {
                $subscription = \Stripe\Subscription::retrieve($subId);
                $item = $subscription->items->data[0] ?? null;
                if ($item) {
                    $priceId   = $item->price->id ?? null;
                    $productId = $item->price->product ?? null;
                }
            }

            // 2) 如果没拿到 memberId，用 email 兜底匹配你站内用户
            if (!$memberId && $email) {
                $memberId = DB::table('member')->where('email', $email)->value('id');
            }

            Log::info('checkout.completed links', [
                'client_reference_id' => $session->client_reference_id ?? null,
                'member_id_resolved'  => $memberId,
                'email'               => $email,
                'customer'            => $cusId,
                'subscription'        => $subId,
            ]);

            DB::table('payments')->updateOrInsert(
                ['stripe_session_id' => $sessId],
                [
                    'member_id'              => $memberId,   // ← 关键：写入
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
                DB::table('member')->where('id', $memberId)
                    ->update(['stripe_customer_id' => $cusId]);
            }

            Log::info('saved payment', ['member_id' => $memberId, 'customer' => $cusId, 'session' => $sessId]);
            } catch (\Throwable $e) {
                Log::error('onCheckoutCompleted error: '.$e->getMessage());
            }
    }

    protected function onInvoicePaid($invoice)
    {
        $subId = $invoice->subscription;
        if (!$subId) return;

        // 回填 member_id（以防首次没写上）
        $knownMemberId = DB::table('payments')
            ->where('stripe_subscription_id', $subId)
            ->whereNotNull('member_id')
            ->orderByDesc('id')
            ->value('member_id');

        $update = [
            'status'      => 'paid',
            'raw_payload' => json_encode($invoice),
            'updated_at'  => now(),
        ];
        if ($knownMemberId) $update['member_id'] = $knownMemberId;

        DB::table('payments')->where('stripe_subscription_id', $subId)->update($update);
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
        DB::table('payments')
            ->where('stripe_subscription_id', $subscription->id)
            ->update([
                'status'      => 'canceled',
                'raw_payload' => json_encode($subscription),
                'updated_at'  => now(),
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

}
