<?php

use Illuminate\Support\Facades\Validator;
use PiShift\Content\Fields\Field;
use PiShift\Content\Support\Localizer;

/**
 * @return array<string, list<string>>
 */
function fieldErrors(Field $field, mixed $value): array
{
    $validator = Validator::make(['value' => $value], $field->validationRules('value', Localizer::fromConfig()));

    return $validator->errors()->toArray();
}

it('fills a slug from another field and only accepts lowercase words and dashes', function () {
    $slug = Field::text('slug')->slug('title');

    expect($slug->toSchema())->slugFrom->toBe('title')
        ->and(fieldErrors($slug, 'ceremonie-d-ouverture-2026'))->toBe([])
        ->and(fieldErrors($slug, 'Pas Valide'))->toHaveKey('value')
        ->and(fieldErrors($slug, 'double--dash'))->toHaveKey('value')
        ->and(Field::text('name')->toSchema()['slugFrom'])->toBeNull();
});

it('accepts a select value sent back as a number, but only one of the options', function () {
    $select = Field::select('parent')->options(['2' => 'Two', '5' => 'Five']);

    expect(fieldErrors($select, 2))->toBe([])
        ->and(fieldErrors($select, '5'))->toBe([])
        ->and(fieldErrors($select, 3))->toHaveKey('value')
        ->and(fieldErrors($select, ['2']))->toHaveKey('value');
});

it('requires translatable values in the default locale only', function () {
    $title = Field::text('title')->translatable()->required()->maxLength(10);

    expect(fieldErrors($title, ['ar' => 'عنوان']))->toBe([])
        ->and(fieldErrors($title, ['fr' => 'Titre']))->toHaveKey('value.ar')
        ->and(fieldErrors($title, ['ar' => 'عنوان', 'fr' => str_repeat('x', 11)]))->toHaveKey('value.fr');
});

it('checks emails, numbers and repeater sizes', function () {
    expect(fieldErrors(Field::text('email')->email(), 'not-an-email'))->toHaveKey('value')
        ->and(fieldErrors(Field::number('count')->min(1)->max(9), 12))->toHaveKey('value')
        ->and(fieldErrors(Field::number('count')->min(1)->max(9), 4))->toBe([])
        ->and(fieldErrors(Field::repeater('items', [Field::text('label')])->max(1), [['label' => 'a'], ['label' => 'b']]))->toHaveKey('value');
});

it('describes fields for the admin', function () {
    expect(Field::text('title')->translatable()->required()->label('Title')->help('Shown big')->toSchema())
        ->toMatchArray(['name' => 'title', 'type' => 'text', 'label' => 'Title', 'translatable' => true, 'required' => true, 'help' => 'Shown big'])
        ->and(Field::date('deadline')->visibleWhen('kind', 'tender')->toSchema()['visibleWhen'])->toBe(['field' => 'kind', 'values' => ['tender']]);
});
