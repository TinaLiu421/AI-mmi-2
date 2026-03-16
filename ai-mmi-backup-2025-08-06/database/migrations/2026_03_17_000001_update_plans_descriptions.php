<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdatePlansDescriptions extends Migration
{
    public function up()
    {
        $updates = [
            'all_ai'  => 'Your 24/7 AI migration guide. Perfect for self-starters who want smart support anytime(For 3 months).',
            'hybrid'  => 'AI Smart Plan + 2-hour voice or video call with a qualified migration/education agent',
            'premium' => 'DIY for visa submission with final validation and review by a qualified migration agent',
            'vip'     => 'AI and qualified migration agent support for student, graduate work, working holiday, tourist, and certain family visas',
        ];

        foreach ($updates as $code => $description) {
            DB::table('plans')
                ->where('code', $code)
                ->update(['description' => $description, 'updated_at' => now()]);
        }
    }

    public function down()
    {
        $rollback = [
            'all_ai'  => 'AI migration & visa consultation for 6 months (unlimited)',
            'hybrid'  => 'All AI + 2h agent/lawyer consultation',
            'premium' => 'Hybrid + final validation check',
            'vip'     => 'Premium + blended full service',
        ];

        foreach ($rollback as $code => $description) {
            DB::table('plans')
                ->where('code', $code)
                ->update(['description' => $description, 'updated_at' => now()]);
        }
    }
}
