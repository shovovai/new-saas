<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiReport extends Model
{
    protected $fillable = [
        'website_id', 'type', 'source_report_type', 'source_report_id',
        'summary', 'recommendations', 'risk_level', 'estimated_impact', 'priority', 'model_used',
    ];

    protected function casts(): array
    {
        return [
            'recommendations' => 'array',
        ];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}
