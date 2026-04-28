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
        Schema::table('requests', function (Blueprint $table) {
            $table->unsignedBigInteger('final_signatory_id')->nullable();
            $table->string('final_signatory_name')->nullable();
            $table->string('final_signatory_designation')->nullable();
            $table->string('second_signatory_name')->nullable();
            $table->string('second_signatory_designation')->nullable();
            
            $table->foreign('final_signatory_id')->references('id')->on('signatories');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['final_signatory_id']);
            $table->dropColumn([
                'final_signatory_id',
                'final_signatory_name', 
                'final_signatory_designation',
                'second_signatory_name',
                'second_signatory_designation'
            ]);
        });
    }
};
