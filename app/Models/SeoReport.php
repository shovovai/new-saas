<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoReport extends Model
{
    protected $fillable = [
        'website_id', 'monitoring_job_id', 'score', 'broken_links_count',
        'missing_alt_count', 'has_sitemap', 'has_robots_txt', 'findings', 'raw',
    ];

    protected function casts(): array
    {
        return [
            'has_sitemap' => 'boolean',
            'has_robots_txt' => 'boolean',
            'findings' => 'array',
            'raw' => 'array',
        ];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}
