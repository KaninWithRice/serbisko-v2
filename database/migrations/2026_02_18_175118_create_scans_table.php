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
        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            
            // Added the missing user_id column so your controller doesn't crash!
            $table->unsignedBigInteger('user_id')->nullable();
            
            // Fixed these names to perfectly match your ScanController
            $table->string('document_type'); 
            $table->string('file_path');     
            $table->string('grade_level')->nullable(); 
            
            $table->string('lrn')->nullable(); // Only for Report Cards
            $table->string('status')->default('pending'); // pending, verified, failed
            $table->string('remarks')->nullable(); // e.g., "Grade 10 Verified"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scans');
    }
}; // <--- Added the required semicolon here!