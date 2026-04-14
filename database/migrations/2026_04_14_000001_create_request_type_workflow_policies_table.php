<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_type_workflow_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_type_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('requires_dean_signature')->default(true);
            $table->timestamps();
        });

        $requestTypeIds = DB::table('request_types')->pluck('id');

        if ($requestTypeIds->isNotEmpty()) {
            $now = now();

            DB::table('request_type_workflow_policies')->insert(
                $requestTypeIds->map(static fn (int $requestTypeId) => [
                    'request_type_id' => $requestTypeId,
                    'requires_dean_signature' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('request_type_workflow_policies');
    }
};
