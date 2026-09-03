<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class System extends Model
{
    protected $fillable = ['key', 'value'];

    public static function setProperty(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function getProperty(string $key, $default = null)
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function getProperties(array $keys, $default = null)
    {
        $values = static::query()->whereIn('key', $keys)->pluck('value', 'key')->all();

        foreach ($keys as $key) {
            $values[$key] = $values[$key] ?? $default;
        }

        return $values;
    }
}
