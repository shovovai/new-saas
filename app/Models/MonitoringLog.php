<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringLog extends Model
{
    protected $fillable = ['website_id', 'check_type', 'status', 'metric_value', 'details'];

    protected function casts(): array
    {
        return [
            'details' => 'array',
        ];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}
