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
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                $row = static::where('key', $key)->first();
                if ($row !== null && $row->value !== null && $row->value !== '') {
                    try {
                        $decrypted = Crypt::decryptString($row->value);
                        if ($decrypted !== null && $decrypted !== '') {
                            return $decrypted;
                        }
                    } catch (\Throwable) {
                        // Decryption failed (e.g. APP_KEY changed); fall through to env
                    }
                }
            }
        } catch (\Throwable) {
            // Table missing or schema error; fall through to env
        }

        // Fallback: env so the key works at runtime even when config is cached
        if ($key === 'openrouter_api_key') {
            $envKey = env('OPENROUTER_API_KEY');
            if ($envKey !== null && trim((string) $envKey) !== '') {
                return trim((string) $envKey);
            }
        }

        return null;
    }

    public static function setValue(string $key, string $plain): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('settings')) {
            throw new \RuntimeException('Settings table does not exist. Run: php artisan migrate');
        }
        $encrypted = Crypt::encryptString($plain);
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $encrypted]
        );
    }
}
