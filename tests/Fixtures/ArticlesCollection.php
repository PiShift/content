<?php

namespace PiShift\Content\Tests\Fixtures;

use Illuminate\Validation\Rule;
use PiShift\Content\Collections\Action;
use PiShift\Content\Collections\CollectionType;
use PiShift\Content\Collections\Drivers\CollectionDriver;
use PiShift\Content\Collections\Drivers\EloquentDriver;
use PiShift\Content\Fields\Field;

class ArticlesCollection extends CollectionType
{
    public function fields(): array
    {
        return [
            Field::text('title')->translatable()->required(),
            Field::text('slug')->slug('title')->required(),
            Field::select('kind')->options(['news' => 'News', 'tender' => 'Tender'])->required(),
            Field::date('deadline')->visibleWhen('kind', 'tender')->required(),
            Field::select('status')->options(['draft' => 'Draft', 'published' => 'Published']),
            Field::number('views')->readonly(),
        ];
    }

    public function driver(): CollectionDriver
    {
        return new EloquentDriver(Article::class);
    }

    public function rules(mixed $recordId = null): array
    {
        return ['slug' => [Rule::unique('articles', 'slug')->ignore($recordId)]];
    }

    public function filters(): array
    {
        return ['kind' => ['news' => 'News', 'tender' => 'Tender']];
    }

    public function searchable(): array
    {
        return ['slug'];
    }

    public function badges(): array
    {
        return ['status' => ['draft' => 'neutral', 'published' => 'success']];
    }

    public function actions(): array
    {
        return [
            Action::make('publish')
                ->label('Publish')
                ->visible(fn (Article $article): bool => $article->status === 'draft')
                ->handle(function (Article $article): string {
                    $article->update(['status' => 'published']);

                    return 'Published.';
                }),
        ];
    }

    public function navigationBadge(): ?int
    {
        return Article::where('status', 'draft')->count() ?: null;
    }
}
