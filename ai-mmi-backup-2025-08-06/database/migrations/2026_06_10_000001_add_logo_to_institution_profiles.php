<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLogoToInstitutionProfiles extends Migration
{
    public function up()
    {
        Schema::table('institution_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('institution_profiles', 'logo')) {
                $table->string('logo', 255)->nullable()->after('gallery_json');
            }
        });
    }

    public function down()
    {
        Schema::table('institution_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('institution_profiles', 'logo')) {
                $table->dropColumn('logo');
            }
        });
    }
}
