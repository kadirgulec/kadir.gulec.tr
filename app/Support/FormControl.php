<?php

namespace App\Support;

use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;

/**
 * Shared naming rules of the admin form controls: the field name comes from
 * `name` or `wire:model`, the id from `id` or the name. The name is also the
 * key of the validation error, like in Flux.
 */
class FormControl
{
    public static function name(ComponentAttributeBag $attributes, ?string $name = null): ?string
    {
        if (filled($name)) {
            return $name;
        }

        $model = $attributes->whereStartsWith('wire:model')->getAttributes();

        return filled($model) ? (string) reset($model) : null;
    }

    public static function id(ComponentAttributeBag $attributes, ?string $name): string
    {
        $id = $attributes->get('id');

        if (filled($id)) {
            return (string) $id;
        }

        return $name !== null
            ? 'field-'.Str::slug(str_replace('.', '-', $name))
            : 'field-'.Str::lower(Str::random(8));
    }

    /**
     * The aria-describedby value pointing at the description and error.
     */
    public static function describedBy(string $id, ?string $description, ?string $error): ?string
    {
        $ids = array_filter([
            filled($description) ? $id.'-description' : null,
            filled($error) ? $id.'-error' : null,
        ]);

        return $ids === [] ? null : implode(' ', $ids);
    }
}
