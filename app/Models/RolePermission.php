<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class RolePermission extends Model
{
    protected $fillable = ['role', 'permission_id'];

    protected static function booted(): void
    {
        static::saved(fn (self $rp) => Cache::forget("role_permissions:{$rp->role}"));
        static::deleted(fn (self $rp) => Cache::forget("role_permissions:{$rp->role}"));
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }
}
