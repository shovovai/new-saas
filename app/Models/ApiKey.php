<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $fillable = ['team_id', 'created_by', 'name', 'key_prefix', 'key_hash', 'last_used_at', 'revoked_at'];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Generates a new plaintext key (shown once) and its hashed row.
     *
     * @return array{model: ApiKey, plaintext: string}
     */
    public static function generate(Team $team, User $creator, string $name): array
    {
        $plaintext = 'sgai_'.Str::random(40);

        $model = static::create([
            'team_id' => $team->id,
            'created_by' => $creator->id,
            'name' => $name,
            'key_prefix' => substr($plaintext, 0, 12),
            'key_hash' => Hash::make($plaintext),
        ]);

        return ['model' => $model, 'plaintext' => $plaintext];
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }
}
