<?php

namespace Willypelz\LogPlatform\Models;

use Illuminate\Database\Eloquent\Model;

class AlertEvent extends Model
{
    protected $table = 'log_platform_alert_events';

    protected $fillable = [
        'alert_rule_id',
        'triggered_at',
        'match_count',
        'payload',
        'delivery_status',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
        'match_count' => 'integer',
        'payload' => 'array',
        'delivery_status' => 'array',
    ];

    /**
     * Get the alert rule.
     */
    public function rule()
    {
        return $this->belongsTo(AlertRule::class, 'alert_rule_id');
    }
}

