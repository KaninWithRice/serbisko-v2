<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sync_conflicts', function (Blueprint $table) 
        {
            $table->id();
            $table->string('lrn');
            $table->string('school_year');
            
            // 1. CHANGE: Use setNull instead of cascade. 
            // If a user is deleted, you usually want to keep the conflict record 
            // for auditing purposes rather than deleting the history.
            $table->foreignId('existing_user_id')
                  ->nullable() 
                  ->constrained('users')
                  ->onDelete('set null'); 
            
            $table->json('existing_data_json')->nullable();
            $table->json('incoming_data_json'); 

            // 2. ADD: Store the literal raw row from the Google Sheet.
            // This is a lifesaver if there is a mapping error and you need to see 
            // exactly what was in the spreadsheet (Column A, B, C, etc).
            $table->json('raw_sheet_row')->nullable();
            
            // 3. INDEX: Add an index to conflict_type for dashboard filtering.
            $table->string('conflict_type')->default('identity_mismatch')->index();
            
            $table->enum('status', ['pending', 'resolved', 'ignored'])->default('pending');
            $table->string('resolution_action')->nullable(); 
            
            $table->foreignId('resolved_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            
            $table->timestamps();

            // Prevents duplicate conflict rows for the same student+year
            $table->unique(['lrn', 'school_year']);
            
            // Primary dashboard query: show all pending conflicts
            $table->index(['lrn', 'school_year', 'status', 'conflict_type']);
        });
    }
    
    public function down(): void {
        Schema::dropIfExists('sync_conflicts');
    }
};