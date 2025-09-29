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
            $subId = $session->subscription ?? null;
            $cusId = $session->customer ?? null;
            $sessId = $session->id ?? null;

            $priceId = null; $productId = null;
            $amount  = $session->amount_total ?? null; // 订阅时常为 null
            $currency= $session->currency ?? null;

            if ($subId) {
                $subscription = Subscription::retrieve($subId); 
                $item = $subscription->items->data[0] ?? null;
                if ($item) {
                    $priceId   = $item->price->id ?? null;
                    $productId = $item->price->product ?? null;
                }
            }

            \DB::table('payments')->updateOrInsert(
                ['stripe_session_id' => $sessId],
                [
                    'user_id'                => null,
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

            \Log::info('onCheckoutCompleted saved payment', ['session' => $sessId, 'sub' => $subId, 'price' => $priceId]);
        } catch (\Throwable $e) {
            \Log::error('onCheckoutCompleted error: '.$e->getMessage());
            // 仍返回 200 避免 Stripe 重试风暴
        }
    }

    protected function onInvoicePaid($invoice)
    {
        // 续费成功
        $subId = $invoice->subscription;
        $cusId = $invoice->customer;

        DB::table('payments')
            ->where('stripe_subscription_id', $subId)
            ->update([
                'status'      => 'paid',
                'raw_payload' => json_encode($invoice),
                'updated_at'  => now(),
            ]);
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
}
