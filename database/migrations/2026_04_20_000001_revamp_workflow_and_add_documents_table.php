<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop legacy workflow policy table
        Schema::dropIfExists('request_type_workflow_policies');

        // Remove dead columns from requests
        Schema::table('requests', function (Blueprint $table) {
            $columns = Schema::getColumnListing('requests');

            if (in_array('deadline', $columns)) $table->dropColumn('deadline');
            if (in_array('is_priority', $columns)) $table->dropColumn('is_priority');
            if (in_array('snapshot_requires_dean_signature', $columns)) $table->dropColumn('snapshot_requires_dean_signature');
            if (in_array('dean_signature_data', $columns)) $table->dropColumn('dean_signature_data');
            if (in_array('dean_signed_at', $columns)) $table->dropColumn('dean_signed_at');
            if (in_array('dean_approved_by', $columns)) {
                try { $table->dropForeign(['dean_approved_by']); } catch (\Exception) {}
                try { $table->dropIndex('requests_dean_approved_by_index'); } catch (\Exception) {}
                $table->dropColumn('dean_approved_by');
            }
            if (in_array('dean_approved_at', $columns)) $table->dropColumn('dean_approved_at');
            if (in_array('dean_notes', $columns)) $table->dropColumn('dean_notes');
            if (in_array('dean_rejection_reason', $columns)) $table->dropColumn('dean_rejection_reason');

            // Add decline_reason and return_reason
            if (!in_array('decline_reason', $columns)) {
                $table->text('decline_reason')->nullable()->after('rejection_reason');
            }
            if (!in_array('return_reason', $columns)) {
                $table->text('return_reason')->nullable()->after('decline_reason');
            }
        });

        // Create documents table
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->string('uploader_role'); // 'user', 'staff2'
            $table->string('file_path');
            $table->string('original_name');
            $table->boolean('is_template')->default(false); // staff2-uploaded template for user to fill
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');

        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn(['decline_reason', 'return_reason']);
            $table->date('deadline')->nullable();
            $table->boolean('is_priority')->default(false);
            $table->boolean('snapshot_requires_dean_signature')->default(false);
        });
    }
};
