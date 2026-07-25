<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Website extends Model
{
    use BelongsToTeam, HasFactory;

    protected $fillable = [
        'team_id', 'created_by', 'name', 'url', 'domain', 'group',
        'status', 'verified_method', 'verified_at', 'paused_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'paused_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(WebsiteVerification::class);
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    /**
     * Latest score snapshot for the 8 dashboard cards. Null values render
     * as locked/unverified/no-scan-yet states in the UI rather than a
     * misleading zero (UIUX §4). Wired to real scan reports in the
     * scanning engine phase — this is the stable shape the frontend reads.
     *
     * @return array<string, int|string|null>
     */
    public function getLatestScoresAttribute(): array
    {
        if (! $this->isVerified()) {
            return [
                'performance' => null, 'seo' => null, 'security' => null, 'accessibility' => null,
                'ssl_status' => null, 'domain_expiry_days' => null,
            ];
        }

        $latestSecurityReport = $this->securityReports()->latest()->first();

        return [
            'performance' => $this->performanceReports()->latest()->value('score'),
            'seo' => $this->seoReports()->latest()->value('score'),
            'security' => $latestSecurityReport?->score,
            'accessibility' => $this->accessibilityReports()->latest()->value('score'),
            'ssl_status' => $this->sslStatusLabel($latestSecurityReport),
            'domain_expiry_days' => $this->monitoringLogs()->where('check_type', 'domain_expiry')->latest()->value('metric_value'),
        ];
    }

    private function sslStatusLabel(?SecurityReport $report): ?string
    {
        if (! $report) {
            return null;
        }

        if (! $report->ssl_valid) {
            return 'invalid';
        }

        if ($report->ssl_expires_at && now()->diffInDays($report->ssl_expires_at, false) < 14) {
            return 'expiring_soon';
        }

        return 'valid';
    }

    public function monitoringJobs(): HasMany
    {
        return $this->hasMany(MonitoringJob::class);
    }

    public function monitoringLogs(): HasMany
    {
        return $this->hasMany(MonitoringLog::class);
    }

    public function performanceReports(): HasMany
    {
        return $this->hasMany(PerformanceReport::class);
    }

    public function seoReports(): HasMany
    {
        return $this->hasMany(SeoReport::class);
    }

    public function securityReports(): HasMany
    {
        return $this->hasMany(SecurityReport::class);
    }

    public function accessibilityReports(): HasMany
    {
        return $this->hasMany(AccessibilityReport::class);
    }

    public function aiReports(): HasMany
    {
        return $this->hasMany(AiReport::class);
    }

    public function aiMessages(): HasMany
    {
        return $this->hasMany(AiMessage::class);
    }

    public function penTestAuthorizations(): HasMany
    {
        return $this->hasMany(PenTestAuthorization::class);
    }

    public function penTestReports(): HasMany
    {
        return $this->hasMany(PenTestReport::class);
    }
}
