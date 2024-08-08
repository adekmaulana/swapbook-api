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
        Schema::create('requests', function (Blueprint $table) {
            // book request data from message
            $table->id();
            $table->foreignId('request_by')->constrained(table: 'users')->cascadeOnDelete();
            $table->foreignId('post_id')->constrained(table: 'posts')->cascadeOnDelete();
            $table->foreignId('message_id')->constrained(table: 'messages')->cascadeOnDelete();
            $table->integer('status')->default(0);  // 0: pending, 1: approved, 2: declined
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
