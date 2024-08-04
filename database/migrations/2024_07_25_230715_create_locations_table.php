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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(table: 'users')->cascadeOnDelete();
            $table->double('latitude');
            $table->double('longitude');
            $table->double('accuracy')->nullable();
            $table->double('altitude')->nullable();
            $table->double('speed')->nullable();
            $table->double('speed_accuracy')->nullable();
            $table->double('heading')->nullable();
            $table->double('time')->nullable();
            $table->integer('is_mock')->nullable();
            $table->double('vertical_accuracy')->nullable();
            $table->double('heading_accuracy')->nullable();
            $table->double('elapsed_realtime_nanos')->nullable();
            $table->double('elapsed_realtime_uncertainty_nanos')->nullable();
            $table->integer('satellite_number')->nullable();
            $table->string('provider')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
