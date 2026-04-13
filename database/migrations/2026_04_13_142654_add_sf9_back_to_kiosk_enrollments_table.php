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
        Schema::table('kiosk_enrollments', function (Blueprint $table) {
            $table->string('sf9_back_path')->nullable()->after('sf9_attempts');
            $table->string('sf9_back_status')->default('pending')->after('sf9_back_path');
            $table->string('sf9_back_remarks')->nullable()->after('sf9_back_status');
            $table->integer('sf9_back_attempts')->default(0)->after('sf9_back_remarks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kiosk_enrollments', function (Blueprint $table) {
            $table->dropColumn(['sf9_back_path', 'sf9_back_status', 'sf9_back_remarks', 'sf9_back_attempts']);
        });
    }
};