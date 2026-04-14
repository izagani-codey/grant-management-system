<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_type_templates', function (Blueprint $table) {
            // 'two_signatures' = Admission + Staff2 only
            // 'three_signatures' = Admission + Staff2 + Dean
            // 'any' = general/fallback template (default for backward compatibility)
            $table->string('signature_layout')->default('any')->after('is_default');
        });
    }

    public function down(): void
    {
        Schema::table('request_type_templates', function (Blueprint $table) {
            $table->dropColumn('signature_layout');
        });
    }
};
