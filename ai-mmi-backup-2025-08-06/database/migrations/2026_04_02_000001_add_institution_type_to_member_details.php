<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInstitutionTypeToMemberDetails extends Migration
{
    public function up()
    {
        Schema::table('app_member_details', function (Blueprint $table) {
            // 0 = not set/migration default, 1 = migration_institution, 2 = education_institution
            $table->tinyInteger('institution_type')->default(0)->after('company_website');
        });

        Schema::create('app_institution_profiles', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('member_id')->default(0);
            $table->text('website_url')->nullable();
            $table->string('institute_name', 500)->nullable();
            $table->mediumText('programs')->nullable();
            $table->mediumText('admission')->nullable();
            $table->mediumText('fees')->nullable();
            $table->mediumText('summary')->nullable();
            $table->mediumText('key_dates')->nullable();
            $table->integer('students_matched')->default(0);
            $table->integer('students_applied')->default(0);
            $table->integer('students_accepted')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->integer('created_by')->default(0);
            $table->dateTime('created_at')->nullable();
            $table->integer('updated_by')->default(0);
            $table->dateTime('updated_at')->nullable();
            $table->integer('deleted_by')->default(0);
            $table->dateTime('deleted_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('app_member_details', function (Blueprint $table) {
            $table->dropColumn('institution_type');
        });
        Schema::dropIfExists('app_institution_profiles');
    }
}
