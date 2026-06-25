<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTokenTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('token_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->enum('type', [
                'earn_signup',
                'earn_daily_login',
                'earn_profile_complete',
                'earn_share_results',
                'earn_referral_accepted',
                'earn_admin_grant',
                'purchase',
                'spend_chat',
                'spend_match',
                'spend_agent_call',
                'spend_diy_visa',
                'spend_full_assistance',
                'spend_school_payment',
                'spend_admin_deduct',
                'transfer_out',
                'transfer_in',
            ])->comment('Transaction type');
            $table->bigInteger('amount')->comment('Positive = credit, negative = debit');
            $table->unsignedBigInteger('balance_after')->default(0)->comment('Balance after this transaction');
            $table->string('reference_type', 50)->nullable()->comment('e.g. payment, member, chat_log');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('Related record ID');
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index('member_id');
            $table->index(['member_id', 'type']);
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('token_transactions');
    }
}
