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
            $memberId = $session->client_reference_id ?? null;

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

                    // ⚡ 核心逻辑：只对 migration 类覆盖，其它(education)直接并行
                    if ($plan->business_domain === 'migration') {
                        DB::table('subscriptions')
                            ->where('member_id', $memberId)
                            ->where('status', 'active')
                            ->whereIn('plan_id', function($q){
                                $q->select('id')->from('plans')->where('business_domain','migration');
                            })
                            ->update(['status'=>'expired','updated_at'=>now()]);
                    }

                    // 幂等检查
                    $exists = DB::table('subscriptions')
                        ->where('stripe_subscription_id', $subId)
                        ->exists();

                    if (!$exists) {
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
                    } else {
                        Log::info('invoice.paid → subscriptions exists', ['subId'=>$subId]);
                    }
                } else {
                    Log::warning('invoice.paid price 未映射到 plan', ['price_id'=>$priceId]);
                }
            } else {
                Log::warning('invoice.paid 缺少 member 或 price，跳过创建订阅', [
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
