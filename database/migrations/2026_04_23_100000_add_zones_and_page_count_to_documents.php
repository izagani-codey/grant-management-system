<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->json('zones')->nullable()->after('field_zones');
            $table->unsignedInteger('pdf_page_count')->nullable()->default(null)->after('zones');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['zones', 'pdf_page_count']);
        });
    }
};
