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
        Schema::create('checklist_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
            $table->foreignId('checklist_item_id')->constrained('checklist_items')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->constrained('users');
            $table->enum('status', ['checked', 'flagged']); // checked = OK, flagged = problem
            $table->text('note')->nullable(); // Optional note for flagged items
            $table->timestamps();
            
            $table->unique(['request_id', 'checklist_item_id']); // One review per item per request
            $table->index(['request_id', 'status']);
            $table->index(['reviewed_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklist_reviews');
    }
};
