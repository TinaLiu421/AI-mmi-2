<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $t) {
            $t->unsignedBigInteger('member_id')->nullable()->after('id');
            $t->index('member_id');
        });
    }
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $t) {
            $t->dropIndex(['member_id']);
            $t->dropColumn('member_id');
        });
    }
};
