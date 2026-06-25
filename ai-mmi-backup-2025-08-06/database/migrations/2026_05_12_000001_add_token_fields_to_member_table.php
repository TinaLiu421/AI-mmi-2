<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTokenFieldsToMemberTable extends Migration
{
    public function up()
    {
        Schema::table('member', function (Blueprint $table) {
            $table->unsignedBigInteger('token_balance')->default(0)->after('status')->comment('AI-mmi token balance');
            $table->string('referral_code', 16)->nullable()->unique()->after('token_balance')->comment('Unique referral code for this member');
            $table->unsignedBigInteger('referred_by_member_id')->nullable()->after('referral_code')->comment('Member ID who referred this user');
            $table->date('last_daily_token_date')->nullable()->after('referred_by_member_id')->comment('Last date daily login token was awarded');
            $table->boolean('profile_token_awarded')->default(false)->after('last_daily_token_date')->comment('Whether profile-complete token has been awarded');
        });
    }

    public function down()
    {
        Schema::table('member', function (Blueprint $table) {
            $table->dropColumn([
                'token_balance',
                'referral_code',
                'referred_by_member_id',
                'last_daily_token_date',
                'profile_token_awarded',
            ]);
        });
    }
}
