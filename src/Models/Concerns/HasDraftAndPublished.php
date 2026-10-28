<?php

namespace PiShift\Content\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use PiShift\Content\Models\Revision;

/**
 * Draft/published content columns: editors save drafts, publishing copies the draft to the
 * live column and keeps a revision.
 */
trait HasDraftAndPublished
{
    public function revisions(): MorphMany
    {
        return $this->morphMany(Revision::class, 'revisionable')->latest('id');
    }

    public function hasUnpublishedChanges(): bool
    {
        return $this->draft !== null && $this->draft != $this->published;
    }

    public function publish(?int $userId = null): static
    {
        $this->published = $this->draft;
        $this->published_at = now();
        $this->save();

        $this->revisions()->create(['content' => $this->published ?? [], 'user_id' => $userId]);

        return $this;
    }

    /**
     * Put an older revision back into the draft (publish to make it live).
     */
    public function restore(Revision $revision): static
    {
        $this->draft = $revision->content;
        $this->save();

        return $this;
    }
}
