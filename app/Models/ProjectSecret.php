<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class ProjectSecret extends Model
{
    protected $fillable = ['project_id', 'key', 'value'];

    protected $hidden = ['value'];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getDecryptedValue(): ?string
    {
        if ($this->value === null || $this->value === '') {
            return null;
        }
        try {
            return Crypt::decryptString($this->value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function setDecryptedValue(string $plain): void
    {
        $this->value = Crypt::encryptString($plain);
    }
}
