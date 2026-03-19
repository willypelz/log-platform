<?php

namespace Willypelz\LogPlatform\Jobs;

use Willypelz\LogPlatform\Models\IndexedLog;
use Willypelz\LogPlatform\Models\MetricTimeseries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class AggregateMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        protected string $env,
        protected \DateTimeInterface $bucketStart,
        protected int $bucketSize = 60
    ) {
    }

    public function handle(): void
    {
        $bucketEnd = (clone $this->bucketStart)->modify("+{$this->bucketSize} seconds");

        // Aggregate error count
        $errorCount = IndexedLog::environment($this->env)
            ->whereIn('level', ['error', 'critical', 'alert', 'emergency'])
            ->whereBetween('logged_at', [$this->bucketStart, $bucketEnd])
            ->count();

        MetricTimeseries::updateOrCreate(
            [
                'env' => $this->env,
                'metric' => 'errors_per_minute',
                'bucket_start' => $this->bucketStart,
                'bucket_size' => $this->bucketSize,
            ],
            ['value' => $errorCount]
        );

        // Aggregate total log count
        $totalCount = IndexedLog::environment($this->env)
            ->whereBetween('logged_at', [$this->bucketStart, $bucketEnd])
            ->count();

        MetricTimeseries::updateOrCreate(
            [
                'env' => $this->env,
                'metric' => 'logs_per_minute',
                'bucket_start' => $this->bucketStart,
                'bucket_size' => $this->bucketSize,
            ],
            ['value' => $totalCount]
        );

        // Aggregate by level
        $levelCounts = IndexedLog::environment($this->env)
            ->whereBetween('logged_at', [$this->bucketStart, $bucketEnd])
            ->select('level', DB::raw('count(*) as count'))
            ->groupBy('level')
            ->get();

        foreach ($levelCounts as $levelCount) {
            MetricTimeseries::updateOrCreate(
                [
                    'env' => $this->env,
                    'metric' => 'logs_' . $levelCount->level,
                    'bucket_start' => $this->bucketStart,
                    'bucket_size' => $this->bucketSize,
                ],
                ['value' => $levelCount->count]
            );
        }
    }
}

