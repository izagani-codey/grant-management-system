<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->index('status_id');
            $table->index('user_id');
            $table->index('request_type_id');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->index('request_id');
            $table->index('document_type');
        });

        Schema::table('checklist_reviews', function (Blueprint $table) {
            $table->index('request_id');
            $table->index('checklist_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropIndex(['status_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['request_type_id']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['request_id']);
            $table->dropIndex(['document_type']);
        });

        Schema::table('checklist_reviews', function (Blueprint $table) {
            $table->dropIndex(['request_id']);
            $table->dropIndex(['checklist_item_id']);
        });
    }
};
