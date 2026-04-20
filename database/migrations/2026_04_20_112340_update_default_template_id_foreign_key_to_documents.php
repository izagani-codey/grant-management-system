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
        // Drop existing foreign key if it exists and add new one
        if (Schema::hasTable('request_types') && Schema::hasColumn('request_types', 'default_template_id')) {
            Schema::table('request_types', function (Blueprint $table) {
                // Drop existing foreign key constraint
                $table->dropForeign(['default_template_id']);
            });
            
            // Add new foreign key constraint to documents table
            Schema::table('request_types', function (Blueprint $table) {
                $table->foreign('default_template_id')->references('id')->on('documents')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse: Drop foreign key to documents and add back foreign key to form_templates
        if (Schema::hasTable('request_types') && Schema::hasColumn('request_types', 'default_template_id')) {
            Schema::table('request_types', function (Blueprint $table) {
                $table->dropForeign(['default_template_id']);
            });
            
            // Add back foreign key constraint to form_templates table (legacy) if it exists
            if (Schema::hasTable('form_templates')) {
                Schema::table('request_types', function (Blueprint $table) {
                    $table->foreign('default_template_id')->references('id')->on('form_templates')->onDelete('set null');
                });
            }
        }
    }
};
