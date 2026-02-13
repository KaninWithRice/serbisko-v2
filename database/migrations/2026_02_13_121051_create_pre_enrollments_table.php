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
        Schema::create('pre_enrollments', function (Blueprint $table) {
            $table->id(); // Internal enrollment ID
            
            // Link to the student using their LRN
            $table->string('student_lrn');
            
            // Set up the relationship: If the student is deleted, their form is too
            $table->foreign('student_lrn')
                  ->references('lrn')
                  ->on('students')
                  ->onDelete('cascade');

            // The Flexible JSON Column for the rest of the Google Form answers
            $table->json('responses'); 
            
            // Track the application status (e.g., Pending, Verified, Done)
            $table->string('status')->default('Pending'); 
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_enrollments');
    }
};