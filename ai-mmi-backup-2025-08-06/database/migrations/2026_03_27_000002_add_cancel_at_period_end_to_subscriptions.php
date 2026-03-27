<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCancelAtPeriodEndToSubscriptions extends Migration
{
    public function up()
    {
        Schema::table('subscriptions', function (Blueprint $t) {
            $t->boolean('cancel_at_period_end')->default(false)->after('stripe_subscription_id');
        });
    }

    public function down()
    {
        Schema::table('subscriptions', function (Blueprint $t) {
            $t->dropColumn('cancel_at_period_end');
        });
    }
}
