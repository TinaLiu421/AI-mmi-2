<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('member_business_license', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('member_id')->default(0)->index();
            $table->unsignedInteger('license_country')->default(0)->comment('Country ID where license is issued');
            $table->string('issuing_authority', 255)->nullable()->comment('Authority that issued the license');
            $table->string('type_of_registration', 255)->nullable()->comment('Type of registration or license');
            $table->string('registration_number', 255)->nullable()->comment('Business license or registration number');
            $table->tinyInteger('status')->default(1);
            $table->unsignedInteger('created_by')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->unsignedInteger('updated_by')->default(0);
            $table->timestamp('updated_at')->nullable();
            $table->unsignedInteger('deleted_by')->default(0);
            $table->timestamp('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_business_license');
    }
};
