<?php

namespace Willypelz\LogPlatform\Http\Controllers\Api;

use Willypelz\LogPlatform\Models\MetricTimeseries;
use Willypelz\LogPlatform\Models\IndexedLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class MetricsController extends Controller
{
    /**
     * Get metrics overview.
     */
    public function overview(Request $request): JsonResponse
    {
        $env = $request->input('env', config('app.env'));
        $from = $request->input('from', now()->subHour());
        $to = $request->input('to', now());

        // Total logs
        $totalLogs = IndexedLog::environment($env)
            ->dateRange($from, $to)
            ->count();

        // Logs by level
        $byLevel = IndexedLog::environment($env)
            ->dateRange($from, $to)
            ->select('level', DB::raw('count(*) as count'))
            ->groupBy('level')
            ->pluck('count', 'level');

        // Top fingerprints
        $topFingerprints = IndexedLog::environment($env)
            ->dateRange($from, $to)
            ->whereNotNull('fingerprint')
            ->select('fingerprint', 'message', DB::raw('count(*) as count'))
            ->groupBy('fingerprint', 'message')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return response()->json([
            'env' => $env,
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
            'total_logs' => $totalLogs,
            'by_level' => $byLevel,
            'top_fingerprints' => $topFingerprints,
        ]);
    }

    /**
     * Get time-series metrics.
     */
    public function timeseries(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'metric' => 'required|string',
            'env' => 'nullable|string',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'bucket_size' => 'nullable|integer',
        ]);

        $metric = $validated['metric'];
        $env = $validated['env'] ?? config('app.env');
        $from = $validated['from'] ?? now()->subHour();
        $to = $validated['to'] ?? now();
        $bucketSize = $validated['bucket_size'] ?? 60;

        $data = MetricTimeseries::getRange($metric, $env, $from, $to, $bucketSize);

        return response()->json([
            'metric' => $metric,
            'env' => $env,
            'bucket_size' => $bucketSize,
            'data' => $data,
        ]);
    }
}

