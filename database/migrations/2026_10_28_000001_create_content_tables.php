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
        Schema::create('content_pages', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('title')->nullable();
            $table->json('slug')->nullable();
            $table->string('template')->default('default');
            $table->json('draft')->nullable();
            $table->json('published')->nullable();
            $table->json('seo')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('content_revisions', function (Blueprint $table) {
            $table->id();
            $table->morphs('revisionable');
            $table->json('content');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('content_globals', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('draft')->nullable();
            $table->json('published')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('content_media', function (Blueprint $table) {
            $table->id();
            $table->string('disk');
            $table->string('path');
            $table->string('name');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->json('alt')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_media');
        Schema::dropIfExists('content_globals');
        Schema::dropIfExists('content_revisions');
        Schema::dropIfExists('content_pages');
    }
};
