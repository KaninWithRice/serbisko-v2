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
            Schema::create('users', function (Blueprint $table) {
                $table->id(); // Internal Unique ID
                
                // Name Fields
                $table->string('first_name')->index(); 
                $table->string('last_name')->index();
                $table->string('middle_name')->nullable();
                
                // Login Identifiers
                $table->date('birthday')->index(); 
                $table->string('password'); // This will store the hashed LRN or Temp ID
                
                // Access Control
                $table->enum('role', ['admin', 'teacher', 'student'])->default('student');
                
                $table->timestamps(); // created_at and updated_at
            });
        }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
