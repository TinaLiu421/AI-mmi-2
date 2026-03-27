@extends('web.common')
@section('content')
<div class="inner-panel">
    <h1 class="title"><?php echo $_page_lang['choose_a_plan']; ?></h1>
    <?php if(!empty($_page_data['details']['content'])) { ?>
    <div class="top-brief iweb-editor">
        <?php echo $_page_data['details']['content']; ?>
        <div class="clearboth"></div>
    </div>
    <?php } ?>
    <div class="underline"></div>
    <div class="clearboth"></div>

    <!-- Free account options (Individual / Service Provider) -->
    <?php if(!empty($_page_data['list_account_plans'])) { ?>
    <div class="list"><!--
        <?php foreach ($_page_data['list_account_plans'] as $key => $plan) { ?>
        <?php if($plan['type'] == 'migration_agent') continue; // Hide migration agent for now ?>
        --><div class="block plan">
            <div class="txt-1"><?php echo $_page_lang['sign_up_as']; ?></div>

            <div class="txt-2"><?php echo $plan['title'];?></div>

            <?php if($plan['type'] == 'service_provider') { ?>
            <!-- Service Provider: Always Free -->
            <div class="txt-3"><?php echo $_page_lang['free'];?></div>
            <div class="txt-4">&nbsp;</div>
            <div class="link-1">
                <a href="<?php echo $_page_base_url.'/account_registration/'.$plan['type'] ;?>"><?php echo $_page_lang['sign_up_now']; ?></a>
            </div>
            <div class="link-2">
                <a href="#">&nbsp;</a>
            </div>
            <?php } else { ?>
            <!-- Other account types: Show trial/pricing -->
            <?php if((int)$plan['valid_days_trial'] > 0) { ?>
            <div class="txt-3"><?php echo $_page_lang['free_trial'];?><span> /<?php echo str_replace('{num}', $plan['valid_days_trial'], $_page_lang['num_days']);?></span></div>
            <?php } else { ?>
            <div class="txt-3"><?php echo $_page_lang['free'];?></div>
            <?php } ?>

            <?php if((int)$plan['price'] > 0) { ?>
            <div class="txt-4"><?php echo str_replace('{price}', number_format($plan['price']), $_page_lang['price']); ?></div>
            <?php } else { ?>
            <div class="txt-4">&nbsp;</div>
            <?php } ?>

            <div class="link-1">
                <a href="<?php echo $_page_base_url.'/account_registration/'.$plan['type'] ;?>"><?php echo $_page_lang['sign_up_now']; ?></a>
            </div>

            <?php if((int)$plan['valid_days_trial'] > 0) { ?>
            <div class="link-2">
                <a href="<?php echo $_page_base_url.'/account_registration/'.$plan['type'] ;?>?trial=1"><?php echo $_page_lang['or_start_free_trial']; ?></a>
            </div>
            <?php } else { ?>
            <div class="link-2">
                <a href="#">&nbsp;</a>
            </div>
            <?php } ?>
            <?php } ?>

            <?php if(!empty($plan['content'])) { ?>
            <div class="benefits">
                <span><i class="fa fa-plus"></i> <?php echo $_page_lang['benefits']; ?></span>
                <div class="iweb-editor">
                    <?php echo $plan['content']; ?>
                    <div class="clearboth"></div>
                </div>
            </div>
            <?php } ?>
        </div><!--
        <?php } ?>
    --></div>
    <?php } ?>

    <!-- Paid plan options — sign up + pay in one flow -->
    <div style="margin-top:32px;">
        <h2 style="font-size:22px;font-weight:900;color:var(--primary-blue-dark,#1a3c6e);margin:0 0 6px;">Or sign up with a paid plan</h2>
        <p style="margin:0 0 18px;color:#555;font-size:14px;">Create your account and pay in one step. You'll be taken to secure checkout powered by Stripe.</p>
    </div>
    <style>
        .reg-plan-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        .reg-plan-card {
            position: relative;
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0,0,0,.07);
            padding: 18px;
            display: flex;
            flex-direction: column;
        }
        .reg-plan-card.is-popular {
            border-color: var(--primary-blue, #2563eb);
            box-shadow: 0 4px 16px rgba(37,99,235,.15);
        }
        .reg-popular-badge {
            position: absolute;
            top: -10px;
            right: 14px;
            background: var(--primary-blue, #2563eb);
            color: #fff;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            padding: 4px 10px;
        }
        .reg-plan-name { margin: 0; font-size: 20px; font-weight: 900; color: #1a202c; }
        .reg-plan-period { margin: 4px 0 2px; font-size: 13px; font-weight: 700; color: var(--primary-blue, #2563eb); }
        .reg-plan-renew { margin: 0 0 8px; font-size: 12px; color: #94a3b8; }
        .reg-plan-subtitle { margin: 6px 0 10px; font-size: 13px; color: #555; line-height: 1.5; min-height: 52px; }
        .reg-plan-price { font-size: 36px; font-weight: 900; color: var(--primary-blue-dark, #1a3c6e); margin-bottom: 10px; }
        .reg-plan-cta {
            display: inline-flex; justify-content: center; align-items: center;
            height: 44px; border-radius: 12px; text-decoration: none;
            font-size: 15px; font-weight: 900; color: #fff;
            background: linear-gradient(135deg, #2563eb, #1a3c6e);
            box-shadow: 0 2px 8px rgba(37,99,235,.3);
            margin: 4px 0 12px; width: 100%;
            transition: transform .18s, box-shadow .18s, filter .18s;
        }
        .reg-plan-cta:hover { transform: translateY(-1px); filter: brightness(1.05); color: #fff; text-decoration: none; }
        .reg-features-title { margin: 0 0 6px; font-size: 13px; font-weight: 800; color: #374151; }
        .reg-features-list { margin: 0; padding-left: 18px; font-size: 13px; color: #555; line-height: 1.45; }
        .reg-features-list li + li { margin-top: 5px; }
        @media (max-width: 680px) {
            .reg-plan-grid { grid-template-columns: 1fr; }
            .reg-plan-subtitle { min-height: 0; }
        }
    </style>
    <div class="reg-plan-grid">
        <?php
        $regPaidPlans = [
            [
                'code'         => 'all_ai',
                'name'         => 'AI Smart Plan',
                'period_label' => '(For 90 days)',
                'renew_note'   => 'Auto renews unless cancelled',
                'subtitle'     => 'Your 24/7 AI migration guide. Perfect for self-starters who want smart support anytime.',
                'price'        => '$9',
                'cta'          => 'Sign Up &amp; Subscribe',
                'is_popular'   => false,
                'features'     => [
                    'Unlimited AI migration and visa guidance',
                    'DIY tools for eligibility, document prep, and planning',
                    'Regular policy updates and step-by-step guidance',
                ],
            ],
            [
                'code'         => 'hybrid',
                'name'         => 'AI + Agent Plan',
                'period_label' => '(For 90 days)',
                'renew_note'   => 'Auto renews unless cancelled',
                'subtitle'     => 'AI Smart Plan + 2-hour voice or video call with a qualified migration/education agent',
                'price'        => '$29',
                'cta'          => 'Sign Up &amp; Subscribe',
                'is_popular'   => true,
                'features'     => [
                    'Everything in the AI Smart Plan',
                    '2-hour consultation with a registered migration agent/lawyer',
                    'Personalized feedback and recommendations',
                ],
            ],
            [
                'code'         => 'premium',
                'name'         => 'DIY Plan',
                'period_label' => 'One-time payment',
                'renew_note'   => '',
                'subtitle'     => 'DIY for visa submission with final validation and review by a qualified migration agent',
                'price'        => '$699',
                'cta'          => 'Sign Up &amp; Pay',
                'is_popular'   => false,
                'features'     => [
                    'Everything in the Hybrid Plan',
                    'Final review of your DIY application by a licensed expert',
                    'Detailed recommendations before submission',
                ],
            ],
            [
                'code'         => 'vip',
                'name'         => 'VIP Agent Plan',
                'period_label' => 'One-time payment',
                'renew_note'   => '',
                'subtitle'     => 'AI and qualified migration agent support for student, graduate work, working holiday, tourist, and certain family visas',
                'price'        => '$999',
                'cta'          => 'Sign Up &amp; Pay',
                'is_popular'   => false,
                'features'     => [
                    'Everything in the Premium Plan',
                    'Full guidance and support from a licensed migration agent or lawyer',
                    'Continuous follow-up and personalized support',
                    '*student, graduate work, working holiday, tourist, and certain family visas only',
                ],
            ],
        ];
        foreach ($regPaidPlans as $rp):
        ?>
        <div class="reg-plan-card<?php echo $rp['is_popular'] ? ' is-popular' : ''; ?>">
            <?php if($rp['is_popular']): ?><span class="reg-popular-badge">Most popular</span><?php endif; ?>
            <h3 class="reg-plan-name"><?php echo htmlspecialchars($rp['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <?php if(!empty($rp['period_label'])): ?>
            <p class="reg-plan-period"><?php echo htmlspecialchars($rp['period_label'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <?php if(!empty($rp['renew_note'])): ?>
            <p class="reg-plan-renew"><?php echo htmlspecialchars($rp['renew_note'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <p class="reg-plan-subtitle"><?php echo htmlspecialchars($rp['subtitle'], ENT_QUOTES, 'UTF-8'); ?></p>
            <div class="reg-plan-price"><?php echo htmlspecialchars($rp['price'], ENT_QUOTES, 'UTF-8'); ?></div>
            <a class="reg-plan-cta" href="<?php echo $_page_base_url.'/account_registration/individual?plan='.urlencode($rp['code']); ?>">
                <?php echo $rp['cta']; ?>
            </a>
            <p class="reg-features-title">This includes:</p>
            <ul class="reg-features-list">
                <?php foreach($rp['features'] as $feat): ?>
                <li><?php echo htmlspecialchars($feat, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
    </div>
</div>
@endsection