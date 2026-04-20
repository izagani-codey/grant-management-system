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
        Schema::table('template_usage', function (Blueprint $table) {
            // Drop existing foreign key if it exists
            $table->dropForeign(['template_id']);
            
            // Add new foreign key constraint to documents table
            $table->foreign('template_id')->references('id')->on('documents')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('template_usage', function (Blueprint $table) {
            // Drop foreign key to documents
            $table->dropForeign(['template_id']);
            
            // Recreate foreign key to form_tables (legacy)
            $table->foreign('template_id')->references('id')->on('form_templates')->onDelete('cascade');
        });
    }
};
