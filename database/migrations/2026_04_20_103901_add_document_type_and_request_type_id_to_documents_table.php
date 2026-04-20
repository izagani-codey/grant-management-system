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
            $table->string('document_type')->default('user_submission')->after('original_name'); // 'template', 'user_submission', 'staff_attachment'
            $table->foreignId('request_type_id')->nullable()->constrained('request_types')->nullOnDelete()->after('request_id');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['request_type_id']);
            $table->dropColumn(['document_type', 'request_type_id']);
        });
    }
};
