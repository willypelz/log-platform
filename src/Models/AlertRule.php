<?php

namespace Willypelz\LogPlatform\Models;

use Illuminate\Database\Eloquent\Model;

class AlertRule extends Model
{
    protected $table = 'log_platform_alert_rules';

    protected $fillable = [
        'name',
        'enabled',
        'query',
        'window_seconds',
        'threshold_count',
        'channels',
        'cooldown_seconds',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'window_seconds' => 'integer',
        'threshold_count' => 'integer',
        'channels' => 'array',
        'cooldown_seconds' => 'integer',
    ];

    /**
     * Get alert events.
     */
    public function events()
    {
        return $this->hasMany(AlertEvent::class);
    }

    /**
     * Check if rule is in cooldown.
     */
    public function isInCooldown(): bool
    {
        if (!$this->cooldown_seconds) {
            return false;
        }

        $lastEvent = $this->events()
            ->orderBy('triggered_at', 'desc')
            ->first();

        if (!$lastEvent) {
            return false;
        }

        return $lastEvent->triggered_at->addSeconds($this->cooldown_seconds)->isFuture();
    }
}

