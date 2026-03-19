<?php

namespace Willypelz\LogPlatform\Models;

use Illuminate\Database\Eloquent\Model;

class MetricTimeseries extends Model
{
    protected $table = 'log_platform_metric_timeseries';

    protected $fillable = [
        'env',
        'metric',
        'bucket_start',
        'bucket_size',
        'value',
    ];

    protected $casts = [
        'bucket_start' => 'datetime',
        'bucket_size' => 'integer',
        'value' => 'float',
    ];

    public $timestamps = false;

    /**
     * Get metrics for a date range.
     */
    public static function getRange(string $metric, string $env, $from, $to, int $bucketSize = 60)
    {
        return static::where('metric', $metric)
            ->where('env', $env)
            ->where('bucket_size', $bucketSize)
            ->whereBetween('bucket_start', [$from, $to])
            ->orderBy('bucket_start')
            ->get();
    }
}

