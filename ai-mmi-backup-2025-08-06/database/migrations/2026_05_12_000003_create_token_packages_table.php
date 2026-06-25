<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTokenPackagesTable extends Migration
{
    public function up()
    {
        Schema::create('token_packages', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tokens')->comment('Number of AI-mmi tokens');
            $table->unsignedInteger('price_usd_cents')->comment('Price in USD cents (e.g. 1000 = $10.00)');
            $table->string('stripe_price_id', 100)->nullable()->comment('Stripe Price ID for this package');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed the default packages from the spec
        DB::table('token_packages')->insert([
            ['tokens' => 50,   'price_usd_cents' => 1000, 'is_active' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tokens' => 100,  'price_usd_cents' => 2000, 'is_active' => true, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['tokens' => 300,  'price_usd_cents' => 6000, 'is_active' => true, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['tokens' => 1000, 'price_usd_cents' => 20000,'is_active' => true, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('token_packages');
    }
}
