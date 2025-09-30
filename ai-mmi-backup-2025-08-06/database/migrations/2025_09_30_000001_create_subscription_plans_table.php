<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Free, AI Plan, Hybrid, Premium, VIP
            $table->string('slug')->unique(); // free, ai, hybrid, premium, vip
            $table->string('service_type'); // migration, education
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->integer('duration_months')->default(0); // 0 = unlimited
            $table->string('stripe_price_id')->nullable();
            $table->string('stripe_product_id')->nullable();

            // Feature flags (admin configurable)
            $table->json('features'); // Flexible JSON for any features

            // Example features structure:
            // {
            //   "migration_questions_limit": 5,  // -1 = unlimited
            //   "education_questions_limit": -1,
            //   "ai_consultation": true,
            //   "human_agent_hours": 2,
            //   "validation_check": false,
            //   "full_service": false,
            //   "allowed_visa_types": ["student", "tourist", "skilled", "partner", "child", "graduate_work"]
            // }

            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Seed default plans
        DB::table('subscription_plans')->insert([
            [
                'name' => 'Free Plan',
                'slug' => 'free',
                'service_type' => 'migration',
                'price' => 0,
                'currency' => 'USD',
                'duration_months' => 0,
                'stripe_price_id' => null,
                'stripe_product_id' => null,
                'features' => json_encode([
                    'migration_questions_limit' => 5,
                    'education_questions_limit' => -1, // unlimited
                    'ai_consultation' => false,
                    'human_agent_hours' => 0,
                    'validation_check' => false,
                    'full_service' => false,
                    'allowed_visa_types' => []
                ]),
                'display_order' => 1,
                'is_active' => true,
                'description' => '5 migration questions only, unlimited education questions',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'All AI Plan',
                'slug' => 'ai',
                'service_type' => 'migration',
                'price' => 99.00,
                'currency' => 'USD',
                'duration_months' => 6,
                'stripe_price_id' => env('STRIPE_PRICE_AI'),
                'stripe_product_id' => env('STRIPE_PRODUCT_AI'),
                'features' => json_encode([
                    'migration_questions_limit' => -1, // unlimited
                    'education_questions_limit' => -1,
                    'ai_consultation' => true,
                    'human_agent_hours' => 0,
                    'validation_check' => false,
                    'full_service' => false,
                    'allowed_visa_types' => []
                ]),
                'display_order' => 2,
                'is_active' => true,
                'description' => 'Unlimited migration and visa consultation by AI-mmi for 6 months',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hybrid Plan',
                'slug' => 'hybrid',
                'service_type' => 'migration',
                'price' => 299.00,
                'currency' => 'USD',
                'duration_months' => 6,
                'stripe_price_id' => env('STRIPE_PRICE_HYBRID'),
                'stripe_product_id' => env('STRIPE_PRODUCT_HYBRID'),
                'features' => json_encode([
                    'migration_questions_limit' => -1,
                    'education_questions_limit' => -1,
                    'ai_consultation' => true,
                    'human_agent_hours' => 2,
                    'validation_check' => false,
                    'full_service' => false,
                    'allowed_visa_types' => []
                ]),
                'display_order' => 3,
                'is_active' => true,
                'description' => 'All AI Plan + 2-hour consultation with registered agent/lawyer',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Premium Plan',
                'slug' => 'premium',
                'service_type' => 'migration',
                'price' => 699.00,
                'currency' => 'USD',
                'duration_months' => 6,
                'stripe_price_id' => env('STRIPE_PRICE_PREMIUM'),
                'stripe_product_id' => env('STRIPE_PRODUCT_PREMIUM'),
                'features' => json_encode([
                    'migration_questions_limit' => -1,
                    'education_questions_limit' => -1,
                    'ai_consultation' => true,
                    'human_agent_hours' => 2,
                    'validation_check' => true,
                    'full_service' => false,
                    'allowed_visa_types' => []
                ]),
                'display_order' => 4,
                'is_active' => true,
                'description' => 'Hybrid Plan + final validation check before DIY submission',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'VIP Plan',
                'slug' => 'vip',
                'service_type' => 'migration',
                'price' => 999.00,
                'currency' => 'USD',
                'duration_months' => 6,
                'stripe_price_id' => env('STRIPE_PRICE_VIP'),
                'stripe_product_id' => env('STRIPE_PRODUCT_VIP'),
                'features' => json_encode([
                    'migration_questions_limit' => -1,
                    'education_questions_limit' => -1,
                    'ai_consultation' => true,
                    'human_agent_hours' => -1, // unlimited
                    'validation_check' => true,
                    'full_service' => true,
                    'allowed_visa_types' => ['student', 'graduate_work', 'tourist', 'skilled_independent', 'partner', 'child']
                ]),
                'display_order' => 5,
                'is_active' => true,
                'description' => 'Premium Plan + full blended services (limited visa types)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Education Application',
                'slug' => 'education_app',
                'service_type' => 'education',
                'price' => 100.00,
                'currency' => 'USD',
                'duration_months' => 0,
                'stripe_price_id' => env('STRIPE_PRICE_EDUCATION_APP'),
                'stripe_product_id' => env('STRIPE_PRODUCT_EDUCATION_APP'),
                'features' => json_encode([
                    'migration_questions_limit' => -1,
                    'education_questions_limit' => -1,
                    'program_applications' => 1, // per purchase
                    'ai_consultation' => true,
                    'human_agent_hours' => 0,
                    'validation_check' => false,
                    'full_service' => false,
                    'allowed_visa_types' => []
                ]),
                'display_order' => 6,
                'is_active' => true,
                'description' => 'Per program application fee',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_plans');
    }
}