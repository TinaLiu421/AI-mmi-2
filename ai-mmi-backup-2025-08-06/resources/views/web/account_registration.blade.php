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

    <style>
        /* ── Shared card grid ── */
        .reg-plan-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 10px;
        }

        /* ── Base card ── */
        .reg-plan-card {
            position: relative;
            background: #fff;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0,0,0,.07);
            padding: 22px 24px 20px;
            display: flex;
            flex-direction: column;
        }

        /* ── Free card variant — larger, teal accent ── */
        .reg-plan-card.is-free {
            border-color: #0f9dae;
            box-shadow: 0 4px 16px rgba(15,157,174,.13);
            padding: 28px 28px 24px;
        }
        .reg-plan-card.is-free .reg-plan-label {
            font-size: 13px;
            font-weight: 700;
            color: #0f9dae;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin: 0 0 6px;
        }
        .reg-plan-card.is-free .reg-plan-name  { font-size: 26px; }
        .reg-plan-card.is-free .reg-plan-price { font-size: 48px; color: #0a7b8a; }
        .reg-plan-card.is-free .reg-plan-cta   {
            background: linear-gradient(135deg, #0f9dae, #0a7b8a);
            box-shadow: 0 2px 8px rgba(15,157,174,.35);
            height: 50px;
            font-size: 16px;
        }
        .reg-free-badge {
            position: absolute;
            top: -10px;
            right: 16px;
            background: #0f9dae;
            color: #fff;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            padding: 4px 12px;
        }

        /* ── Popular badge (paid) ── */
        .reg-popular-badge {
            position: absolute;
            top: -10px;
            right: 16px;
            background: var(--primary-blue, #2563eb);
            color: #fff;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            padding: 4px 12px;
        }
        .reg-plan-card.is-popular {
            border-color: var(--primary-blue, #2563eb);
            box-shadow: 0 4px 16px rgba(37,99,235,.15);
        }

        /* ── Typography ── */
        .reg-plan-label  { display: none; } /* only shown on free cards via override above */
        .reg-plan-name   { margin: 0; font-size: 20px; font-weight: 900; color: #1a202c; }
        .reg-plan-period { margin: 4px 0 2px; font-size: 13px; font-weight: 700; color: var(--primary-blue, #2563eb); }
        .reg-plan-renew  { margin: 0 0 8px; font-size: 12px; color: #94a3b8; }
        .reg-plan-subtitle {
            margin: 8px 0 12px;
            font-size: 14px; color: #555; line-height: 1.55;
            min-height: 44px;
        }
        .reg-plan-card.is-free .reg-plan-subtitle { font-size: 15px; min-height: 0; }
        .reg-plan-price  { font-size: 40px; font-weight: 900; color: var(--primary-blue-dark, #1a3c6e); margin-bottom: 12px; line-height: 1; }

        /* ── CTA button ── */
        .reg-plan-cta {
            display: inline-flex; justify-content: center; align-items: center;
            height: 46px; border-radius: 12px; text-decoration: none;
            font-size: 15px; font-weight: 900; color: #fff;
            background: linear-gradient(135deg, #2563eb, #1a3c6e);
            box-shadow: 0 2px 8px rgba(37,99,235,.28);
            margin: 2px 0 14px; width: 100%;
            transition: transform .18s, box-shadow .18s, filter .18s;
        }
        .reg-plan-cta:hover {
            transform: translateY(-1px); filter: brightness(1.06);
            color: #fff; text-decoration: none;
        }

        /* ── Feature list ── */
        .reg-features-title { margin: 0 0 7px; font-size: 13px; font-weight: 800; color: #374151; }
        .reg-features-list  { margin: 0; padding-left: 18px; font-size: 13px; color: #555; line-height: 1.5; }
        .reg-features-list li + li { margin-top: 6px; }
        .reg-plan-card.is-free .reg-features-list { font-size: 14px; }

        /* ── Section divider ── */
        .reg-section-head {
            margin: 30px 0 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        .reg-section-head h2 {
            margin: 0 0 4px;
            font-size: 20px; font-weight: 900;
            color: var(--primary-blue-dark, #1a3c6e);
        }
        .reg-section-head p { margin: 0; font-size: 14px; color: #64748b; }

        @media (max-width: 680px) {
            .reg-plan-grid { grid-template-columns: 1fr; }
            .reg-plan-subtitle,
            .reg-plan-card.is-free .reg-plan-subtitle { min-height: 0; }
        }
    </style>

    <!-- ══ FREE PLANS (top, larger) ══ -->
    <div class="reg-section-head">
        <h2>Free plans — get started at no cost</h2>
        <p>Create your account today for free, no credit card required.</p>
    </div>

    <?php if(!empty($_page_data['list_account_plans'])) { ?>
    <div class="reg-plan-grid" style="margin-bottom:0;">
    <?php foreach ($_page_data['list_account_plans'] as $key => $plan): ?>
    <?php if($plan['type'] == 'migration_agent') continue; ?>
        <div class="reg-plan-card is-free">
            <span class="reg-free-badge">Free</span>
            <p class="reg-plan-label"><?php echo $_page_lang['sign_up_as']; ?></p>
            <h3 class="reg-plan-name"><?php echo htmlspecialchars($plan['title'], ENT_QUOTES, 'UTF-8'); ?></h3>

            <?php if($plan['type'] == 'service_provider'): ?>
            <p class="reg-plan-subtitle">List your migration services, connect with clients, and grow your practice — completely free.</p>
            <?php else: ?>
            <?php if((int)$plan['valid_days_trial'] > 0): ?>
            <p class="reg-plan-period"><?php echo $_page_lang['free_trial']; ?> / <?php echo str_replace('{num}', $plan['valid_days_trial'], $_page_lang['num_days']); ?></p>
            <?php endif; ?>
            <p class="reg-plan-subtitle">Explore AI-powered migration guidance and tools to kick-start your journey, completely free.</p>
            <?php endif; ?>

            <div class="reg-plan-price">Free</div>

            <a class="reg-plan-cta" href="<?php echo $_page_base_url.'/account_registration/'.$plan['type']; ?>">
                <?php echo $_page_lang['sign_up_now']; ?>
            </a>

            <?php if(!empty($plan['content'])): ?>
            <p class="reg-features-title"><i class="fa fa-plus" style="margin-right:4px;"></i><?php echo $_page_lang['benefits']; ?></p>
            <div class="iweb-editor" style="font-size:14px;color:#555;line-height:1.5;">
                <?php echo $plan['content']; ?>
            </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    </div>
    <?php } ?>

    <!-- ══ PAID PLANS ══ -->
    <div class="reg-section-head">
        <h2>Paid plans — sign up &amp; pay in one step</h2>
        <p>Create your account and go straight to secure Stripe checkout.</p>
    </div>

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
            <span class="reg-plan-label"><?php echo $_page_lang['sign_up_as']; ?></span>
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