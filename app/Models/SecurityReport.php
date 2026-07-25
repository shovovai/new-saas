<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityReport extends Model
{
    protected $fillable = [
        'website_id', 'monitoring_job_id', 'score', 'ssl_valid', 'ssl_expires_at',
        'has_sensitive_file_exposure', 'missing_headers', 'findings', 'raw',
    ];

    protected function casts(): array
    {
        return [
            'ssl_valid' => 'boolean',
            'ssl_expires_at' => 'datetime',
            'has_sensitive_file_exposure' => 'boolean',
            'missing_headers' => 'array',
            'findings' => 'array',
            'raw' => 'array',
        ];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}
