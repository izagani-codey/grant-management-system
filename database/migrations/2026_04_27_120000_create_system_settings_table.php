<?php

use App\Services\SettingsService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->string('type')->default('text');
            $table->string('group')->default('general');
            $table->string('label');
            $table->timestamps();
        });

        foreach (SettingsService::all() as $setting) {
            $setting->save();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
