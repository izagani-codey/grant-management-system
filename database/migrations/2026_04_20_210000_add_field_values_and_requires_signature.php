<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            if (!Schema::hasColumn('requests', 'field_values')) {
                $table->json('field_values')->nullable()->after('payload');
            }
            if (!Schema::hasColumn('requests', 'description')) {
                $table->text('description')->nullable()->after('field_values');
            }
        });

        Schema::table('request_types', function (Blueprint $table) {
            if (!Schema::hasColumn('request_types', 'requires_signature')) {
                $table->boolean('requires_signature')->default(false)->after('requires_vot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn(['field_values', 'description']);
        });
        Schema::table('request_types', function (Blueprint $table) {
            $table->dropColumn('requires_signature');
        });
    }
};
