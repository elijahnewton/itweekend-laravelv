<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->longText('content_html')->nullable();
            $table->text('code_example')->nullable();
            $table->string('code_language')->default('plaintext');
            $table->string('video_url')->nullable();
            $table->integer('order_index')->default(0);
            $table->integer('estimated_minutes')->nullable();
            $table->timestamps();

            $table->unique(['chapter_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
