<?php

namespace PiShift\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A published version of a page or global, kept so it can be restored.
 */
class Revision extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'content_revisions';

    protected $fillable = ['content', 'user_id'];

    protected function casts(): array
    {
        return ['content' => 'array'];
    }

    public function revisionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }
}
