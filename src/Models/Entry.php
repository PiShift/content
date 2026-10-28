<?php

namespace PiShift\Content\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A record of a collection stored by the EntriesDriver.
 *
 * @property array<string, mixed>|null $data
 */
class Entry extends Model
{
    protected $table = 'content_entries';

    protected $fillable = ['collection', 'data', 'position'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'position' => 'integer',
        ];
    }
}
