<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\MemberSubscription;
use Carbon\Carbon;

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
            $subId = $session->subscription ?? null;
            $cusId = $session->customer ?? null;
            $sessId = $session->id ?? null;

            $priceId = null;
            $productId = null;
            $amount  = $session->amount_total ?? null;
            $currency= $session->currency ?? null;

            // Get member_id from session metadata
            $memberId = $session->metadata->member_id ?? null;
            $customerEmail = $session->customer_details->email ?? null;

            // If no member_id in metadata, try to find by email
            if (!$memberId && $customerEmail) {
                $member = DB::table('member')->where('email', $customerEmail)->first();
                $memberId = $member->id ?? null;
            }

            // Retrieve subscription details from Stripe
            $stripeSubscription = null;
            if ($subId) {
                $stripeSubscription = Subscription::retrieve($subId);
                $item = $stripeSubscription->items->data[0] ?? null;
                if ($item) {
                    $priceId   = $item->price->id ?? null;
                    $productId = $item->price->product ?? null;
                }
            }

            // Save payment record
            $payment = DB::table('payments')->updateOrInsert(
                ['stripe_session_id' => $sessId],
                [
                    'user_id'                => $memberId,
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

            // Get payment ID for linking
            $paymentRecord = DB::table('payments')->where('stripe_session_id', $sessId)->first();

            // Find subscription plan by price_id or product_id
            $subscriptionPlan = null;
            if ($priceId) {
                $subscriptionPlan = SubscriptionPlan::findByStripePriceId($priceId);
            }
            if (!$subscriptionPlan && $productId) {
                $subscriptionPlan = SubscriptionPlan::findByStripeProductId($productId);
            }

            // Create member subscription if we have all required data
            if ($memberId && $subscriptionPlan && $stripeSubscription) {
                $expiresAt = null;
                if ($subscriptionPlan->duration_months > 0) {
                    $expiresAt = Carbon::createFromTimestamp($stripeSubscription->current_period_end);
                }

                MemberSubscription::updateOrCreate(
                    [
                        'stripe_subscription_id' => $subId
                    ],
                    [
                        'member_id' => $memberId,
                        'subscription_plan_id' => $subscriptionPlan->id,
                        'payment_id' => $paymentRecord->id ?? null,
                        'stripe_customer_id' => $cusId,
                        'status' => $stripeSubscription->status,
                        'started_at' => Carbon::createFromTimestamp($stripeSubscription->start_date),
                        'expires_at' => $expiresAt,
                    ]
                );

                \Log::info('Created member subscription', [
                    'member_id' => $memberId,
                    'plan' => $subscriptionPlan->slug,
                    'stripe_sub' => $subId
                ]);
            } else {
                \Log::warning('Could not create member subscription - missing data', [
                    'member_id' => $memberId,
                    'plan_found' => $subscriptionPlan ? $subscriptionPlan->slug : 'none',
                    'price_id' => $priceId,
                    'product_id' => $productId
                ]);
            }

            \Log::info('onCheckoutCompleted saved payment', [
                'session' => $sessId,
                'sub' => $subId,
                'price' => $priceId,
                'member' => $memberId
            ]);
        } catch (\Throwable $e) {
            \Log::error('onCheckoutCompleted error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    protected function onInvoicePaid($invoice)
    {
        try {
            $subId = $invoice->subscription;
            $cusId = $invoice->customer;

            // Update payment record
            DB::table('payments')
                ->where('stripe_subscription_id', $subId)
                ->update([
                    'status'      => 'paid',
                    'raw_payload' => json_encode($invoice),
                    'updated_at'  => now(),
                ]);

            // Update member subscription status
            $memberSubscription = MemberSubscription::where('stripe_subscription_id', $subId)->first();
            if ($memberSubscription) {
                $memberSubscription->status = 'active';
                $memberSubscription->save();

                \Log::info('Invoice paid - subscription updated', [
                    'subscription_id' => $subId,
                    'member_id' => $memberSubscription->member_id
                ]);
            }
        } catch (\Throwable $e) {
            \Log::error('onInvoicePaid error: '.$e->getMessage());
        }
    }

    protected function onInvoiceFailed($invoice)
    {
        try {
            $subId = $invoice->subscription;

            // Update payment record
            DB::table('payments')
                ->where('stripe_subscription_id', $subId)
                ->update([
                    'status'      => 'failed',
                    'raw_payload' => json_encode($invoice),
                    'updated_at'  => now(),
                ]);

            // Update member subscription status
            $memberSubscription = MemberSubscription::where('stripe_subscription_id', $subId)->first();
            if ($memberSubscription) {
                $memberSubscription->status = 'past_due';
                $memberSubscription->save();

                \Log::warning('Invoice payment failed', [
                    'subscription_id' => $subId,
                    'member_id' => $memberSubscription->member_id
                ]);
            }
        } catch (\Throwable $e) {
            \Log::error('onInvoiceFailed error: '.$e->getMessage());
        }
    }

    protected function onSubscriptionCanceled($subscription)
    {
        try {
            $subId = $subscription->id;

            // Update payment record
            DB::table('payments')
                ->where('stripe_subscription_id', $subId)
                ->update([
                    'status'      => 'canceled',
                    'raw_payload' => json_encode($subscription),
                    'updated_at'  => now(),
                ]);

            // Update member subscription
            $memberSubscription = MemberSubscription::where('stripe_subscription_id', $subId)->first();
            if ($memberSubscription) {
                $memberSubscription->status = 'canceled';
                $memberSubscription->canceled_at = now();
                $memberSubscription->save();

                \Log::info('Subscription canceled', [
                    'subscription_id' => $subId,
                    'member_id' => $memberSubscription->member_id
                ]);
            }
        } catch (\Throwable $e) {
            \Log::error('onSubscriptionCanceled error: '.$e->getMessage());
        }
    }
}
