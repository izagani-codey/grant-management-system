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
        Schema::table('documents', function (Blueprint $table) {
            $table->string('name')->nullable()->after('document_type');
            $table->text('description')->nullable()->after('name');
            $table->boolean('is_active')->default(true)->after('description');
            $table->integer('download_count')->default(0)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['name', 'description', 'is_active', 'download_count']);
        });
    }
};
