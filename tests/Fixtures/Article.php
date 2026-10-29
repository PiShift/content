<?php

namespace PiShift\Content\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A project model with its own table, managed through the Eloquent driver.
 */
class Article extends Model
{
    protected $fillable = ['title', 'slug', 'kind', 'deadline', 'status', 'views'];

    protected function casts(): array
    {
        return ['title' => 'array', 'deadline' => 'date', 'views' => 'integer'];
    }

    public static function createTable(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->string('slug')->unique();
            $table->string('kind')->default('news');
            $table->date('deadline')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
        });
    }
}
