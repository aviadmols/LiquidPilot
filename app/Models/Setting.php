<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected $hidden = ['value'];

    public static function getValue(string $key): ?string
    {
        $row = static::where('key', $key)->first();
        if ($row === null || $row->value === null || $row->value === '') {
            return null;
        }
        try {
            return Crypt::decryptString($row->value);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function setValue(string $key, string $plain): void
    {
        $encrypted = Crypt::encryptString($plain);
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $encrypted]
        );
    }
}
