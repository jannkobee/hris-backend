<?php

namespace App\Casts;

use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Keeps database TIME columns predictable at the API boundary.
 *
 * MySQL stores these values as HH:mm:ss, while the frontend's time inputs use
 * HH:mm. Seconds are intentionally omitted when reading and restored when
 * writing so forms never receive an ambiguous date-time value.
 */
class TimeOfDay implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr($this->normalize($value), 0, 5);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->normalize($value);
    }

    private function normalize(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('H:i:s');
        }

        $value = trim((string) $value);

        if (! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $value)) {
            throw new InvalidArgumentException("Invalid time value [{$value}]. Expected HH:mm or HH:mm:ss.");
        }

        return strlen($value) === 5 ? "{$value}:00" : $value;
    }
}
