<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFeaturedUntilToMemberPosts extends Migration
{
    public function up()
    {
        Schema::table('member_posts', function (Blueprint $table) {
            // NULL = not featured; datetime = featured until this date (set by admin)
            $table->dateTime('featured_until')->nullable()->default(null)->after('highlight');
        });
    }

    public function down()
    {
        Schema::table('member_posts', function (Blueprint $table) {
            $table->dropColumn('featured_until');
        });
    }
}
