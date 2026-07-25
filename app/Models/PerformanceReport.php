<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceReport extends Model
{
    protected $fillable = [
        'website_id', 'monitoring_job_id', 'score', 'lcp_ms', 'cls', 'inp_ms', 'ttfb_ms', 'findings', 'raw',
    ];

    protected function casts(): array
    {
        return [
            'findings' => 'array',
            'raw' => 'array',
        ];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}
