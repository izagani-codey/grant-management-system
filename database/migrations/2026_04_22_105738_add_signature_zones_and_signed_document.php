<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->json('signature_zones')->nullable()->after('description');
        });

        Schema::table('requests', function (Blueprint $table) {
            $table->unsignedBigInteger('signed_document_id')->nullable()->after('user_id');
            $table->foreign('signed_document_id')->references('id')->on('documents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['signed_document_id']);
            $table->dropColumn('signed_document_id');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('signature_zones');
        });
    }
};
