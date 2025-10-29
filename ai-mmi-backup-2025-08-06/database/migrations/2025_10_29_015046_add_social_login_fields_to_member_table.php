<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSocialLoginFieldsToMemberTable extends Migration
{
    public function up()
    {
        Schema::table('member', function (Blueprint $table) {
            $table->string('social_provider', 20)->nullable()->after('verified')->comment('Social login provider: google, facebook, etc');
            $table->string('social_id', 255)->nullable()->after('social_provider')->comment('Social provider user ID');
            $table->index('social_id');
        });
    }

    public function down()
    {
        Schema::table('member', function (Blueprint $table) {
            $table->dropIndex(['social_id']);
            $table->dropColumn(['social_provider', 'social_id']);
        });
    }
}
