<?php

namespace Mitoop\LaravelQueryBuilder\Support;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use Mitoop\LaravelQueryBuilder\Contracts\ValueResolver;

class ValueHelper
{
    public static function isMeaningless(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    public static function resolveField(int|string $key, mixed $item, array $input): ?array
    {
        $field = is_int($key) ? $item : $key;

        if (self::isMeaningless($field)) {
            return null;
        }

        if (! preg_match('/^(?:([\w-]+):)?([\w.\-$]+)(?:\|([\w-]+))?$/', $field, $matches)) {
            throw new InvalidArgumentException('Invalid field:'.$field);
        }

        $sourceField = $matches[1] ?? null;
        $internalField = $matches[2] ?? null;
        $operator = $matches[3] ?? 'eq';

        if (empty($sourceField)) {
            if (! preg_match('/(?:[\w-]+[.$])?([\w-]+)$/', $internalField, $subMatch)) {
                throw new InvalidArgumentException('Invalid field format:'.$field);
            }
            $sourceField = $subMatch[1];
        }

        $value = Arr::get($input, $sourceField);

        if (! is_int($key)) {
            $isValueResolver = $item instanceof ValueResolver;
            $value = match (true) {
                $isValueResolver && ! self::isMeaningless($value) => $item->resolve($value),
                ! $isValueResolver => $item,
                default => $value,
            };
        }

        if (self::isMeaningless($value)) {
            return null;
        }

        if (in_array($operator, ['is', 'is_not'], true)) {
            if (! $value) {
                return null;
            }
            $value = true;
        }

        $operator = (! in_array($operator, ['in', 'not_in', 'between'], true) && is_array($item)) ? $item : [$operator => $value];

        return [
            'field' => $internalField,
            'mix' => strtolower(Arr::pull($operator, 'mix', 'and')),
            'operator' => $operator,
        ];
    }
}
