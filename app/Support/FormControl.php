<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;

/**
 * Shared naming rules of the admin form controls: the field name comes from
 * `name` or `wire:model`, the id from `id` or the name. The name is also the
 * key of the validation error, like in Flux. Also the value format of
 * datetime-local inputs, where an empty string means "no time".
 */
class FormControl
{
    public static function name(ComponentAttributeBag $attributes, ?string $name = null): ?string
    {
        if (filled($name)) {
            return $name;
        }

        $model = $attributes->whereStartsWith('wire:model')->getAttributes();

        return filled($model) ? (string) reset($model) : $attributes->get('name');
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

    /**
     * The "Y-m-d\TH:i" value of a datetime-local input; empty without a time.
     */
    public static function dateTimeLocal(?CarbonInterface $time): string
    {
        return $time?->format('Y-m-d\TH:i') ?? '';
    }

    public static function parseDateTimeLocal(string $value): ?CarbonImmutable
    {
        return $value !== '' ? CarbonImmutable::parse($value) : null;
    }
}
