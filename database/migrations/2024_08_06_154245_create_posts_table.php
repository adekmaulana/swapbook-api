<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(table: 'users')->cascadeOnDelete();
            $table->string('book_api_id');  // Google Books API ID
            $table->string('api_link');
            $table->string('title');
            $table->jsonb('author')->default(new Expression('(JSON_ARRAY())'));
            $table->jsonb('genre')->default(new Expression('(JSON_ARRAY())'));
            $table->text('synopsis');
            $table->float('average_rating')->nullable(); // value from Google Books API
            $table->integer('rating_count')->nullable(); // value from Google Books API
            $table->float('rating')->nullable(); // user rating
            $table->string('image')->nullable();
            $table->string('image_link')->nullable();
            $table->integer('status')->default(0); // 0: available, 1: swap, 2: finished, 3: deleted
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
