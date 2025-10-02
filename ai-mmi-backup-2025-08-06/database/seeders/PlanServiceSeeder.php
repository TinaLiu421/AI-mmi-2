<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanServiceSeeder extends Seeder {
    public function run() {
        // 1) Services
        $services = [
            ['code'=>'ai_migration_qna','name'=>'AI Migration Q&A','category'=>'migration','unit'=>'questions'],
            ['code'=>'agent_consultation','name'=>'Registered Agent/Lawyer Consultation','category'=>'migration','unit'=>'minutes'],
            ['code'=>'final_validation_check','name'=>'Final Validation Check','category'=>'migration','unit'=>'checks'],
            ['code'=>'vip_full_service','name'=>'Blended Full Service (AI + Agent)','category'=>'migration','unit'=>'unlimited'],
            ['code'=>'education_qna','name'=>'Education Q&A','category'=>'education','unit'=>'unlimited'],
            ['code'=>'education_application','name'=>'Program Application Submission','category'=>'education','unit'=>'applications'],
        ];
        foreach ($services as $s) {
            DB::table('services')->updateOrInsert(['code'=>$s['code']], $s + ['created_at'=>now(),'updated_at'=>now()]);
        }

        // 2) Plans
        $plans = [
            ['code'=>'free','name'=>'Free','duration_months'=>null,'price_usd'=>0,'business_domain'=>'combined','description'=>'Free: Migration 5 Qs; Education unlimited chats'],
            ['code'=>'all_ai','name'=>'All AI Plan','duration_months'=>6,'price_usd'=>99,'business_domain'=>'migration','description'=>'AI migration & visa consultation for 6 months (unlimited)'],
            ['code'=>'hybrid','name'=>'Hybrid Plan','duration_months'=>6,'price_usd'=>299,'business_domain'=>'migration','description'=>'All AI + 2h agent/lawyer consultation'],
            ['code'=>'premium','name'=>'Premium Plan','duration_months'=>6,'price_usd'=>699,'business_domain'=>'migration','description'=>'Hybrid + final validation check'],
            ['code'=>'vip','name'=>'VIP Plan','duration_months'=>6,'price_usd'=>999,'business_domain'=>'migration','description'=>'Premium + blended full service'],
            ['code'=>'application','name'=>'Application Plan','duration_months'=>null,'price_usd'=>100,'business_domain'=>'education','description'=>'Includes 1 program application'],
        ];
        foreach ($plans as $p) {
            DB::table('plans')->updateOrInsert(['code'=>$p['code']], $p + ['created_at'=>now(),'updated_at'=>now()]);
        }

        $planId = fn($code)=>DB::table('plans')->where('code',$code)->value('id');
        $svcId  = fn($code)=>DB::table('services')->where('code',$code)->value('id');

        // 3) Plan ↔ Service entitlements
        $entitlements = [
            // Free
            ['plan'=>'free','svc'=>'ai_migration_qna','quota'=>5,'period_days'=>null,'price'=>null,'notes'=>'Free: 5 questions only'],
            ['plan'=>'free','svc'=>'education_qna','quota'=>null,'period_days'=>null,'price'=>null,'notes'=>'Education chats unlimited'],

            // All AI
            ['plan'=>'all_ai','svc'=>'ai_migration_qna','quota'=>null,'period_days'=>null,'price'=>null,'notes'=>'Unlimited within 6 months'],

            // Hybrid
            ['plan'=>'hybrid','svc'=>'ai_migration_qna','quota'=>null,'period_days'=>null,'price'=>null,'notes'=>'Unlimited within 6 months'],
            ['plan'=>'hybrid','svc'=>'agent_consultation','quota'=>120,'period_days'=>null,'price'=>null,'notes'=>'2 hours free'],

            // Premium
            ['plan'=>'premium','svc'=>'ai_migration_qna','quota'=>null,'period_days'=>null,'price'=>null,'notes'=>'Unlimited within 6 months'],
            ['plan'=>'premium','svc'=>'agent_consultation','quota'=>120,'period_days'=>null,'price'=>null,'notes'=>'2 hours free'],
            ['plan'=>'premium','svc'=>'final_validation_check','quota'=>1,'period_days'=>null,'price'=>null,'notes'=>'One final validation check before DIY submission'],

            // VIP
            ['plan'=>'vip','svc'=>'ai_migration_qna','quota'=>null,'period_days'=>null,'price'=>null,'notes'=>'Unlimited within 6 months'],
            ['plan'=>'vip','svc'=>'agent_consultation','quota'=>120,'period_days'=>null,'price'=>null,'notes'=>'2 hours free'],
            ['plan'=>'vip','svc'=>'final_validation_check','quota'=>1,'period_days'=>null,'price'=>null,'notes'=>'One final validation check'],
            ['plan'=>'vip','svc'=>'vip_full_service','quota'=>null,'period_days'=>null,'price'=>null,'notes'=>'Blended full service (AI + Agent)'],
            ['plan'=>'vip','svc'=>'education_application','quota'=>null,'period_days'=>null,'price'=>null,'notes'=>'Application fee per submission'],

            // Application Plan
            ['plan'=>'application','svc'=>'education_application','quota'=>1,'period_days'=>null,'price'=>null,'notes'=>'One included program application'],
        ];

        foreach ($entitlements as $e) {
            DB::table('plan_entitlements')->updateOrInsert(
                ['plan_id'=>$planId($e['plan']), 'service_id'=>$svcId($e['svc'])],
                [
                    'quota'=>$e['quota'],
                    'period_days'=>$e['period_days'],
                    'price_override_usd'=>$e['price'],
                    'notes'=>$e['notes'],
                    'updated_at'=>now(),
                    'created_at'=>now(),
                ]
            );
        }
    }
}
