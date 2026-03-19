<?php

namespace Willypelz\LogPlatform\Console\Commands;

use Willypelz\LogPlatform\Models\IndexedLog;
use Willypelz\LogPlatform\Models\LogFileState;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LogStatsCommand extends Command
{
    protected $signature = 'log:stats
                            {--env= : Show stats for specific environment}
                            {--from= : From date (Y-m-d)}
                            {--to= : To date (Y-m-d)}';

    protected $description = 'Show log statistics';

    public function handle(): int
    {
        $env = $this->option('env');
        $from = $this->option('from') ? new \DateTime($this->option('from')) : now()->subDay();
        $to = $this->option('to') ? new \DateTime($this->option('to')) : now();

        $query = IndexedLog::dateRange($from, $to);

        if ($env) {
            $query->environment($env);
        }

        // Total logs
        $total = $query->count();

        $this->info("📊 Log Statistics");
        $this->line("Period: {$from->format('Y-m-d H:i')} to {$to->format('Y-m-d H:i')}");
        if ($env) {
            $this->line("Environment: {$env}");
        }
        $this->newLine();

        $this->info("Total Logs: " . number_format($total));
        $this->newLine();

        // By level
        $this->info("By Level:");
        $levels = (clone $query)
            ->select('level', DB::raw('count(*) as count'))
            ->groupBy('level')
            ->orderByDesc('count')
            ->get();

        $headers = ['Level', 'Count', 'Percentage'];
        $rows = $levels->map(function ($item) use ($total) {
            return [
                strtoupper($item->level),
                number_format($item->count),
                $total > 0 ? number_format(($item->count / $total) * 100, 2) . '%' : '0%',
            ];
        });

        $this->table($headers, $rows);
        $this->newLine();

        // By environment
        if (!$env) {
            $this->info("By Environment:");
            $envs = IndexedLog::dateRange($from, $to)
                ->select('env', DB::raw('count(*) as count'))
                ->groupBy('env')
                ->orderByDesc('count')
                ->get();

            $headers = ['Environment', 'Count'];
            $rows = $envs->map(fn($item) => [$item->env, number_format($item->count)]);
            $this->table($headers, $rows);
            $this->newLine();
        }

        // Top fingerprints
        $this->info("Top Error Fingerprints:");
        $fingerprints = (clone $query)
            ->whereIn('level', ['error', 'critical', 'alert', 'emergency'])
            ->whereNotNull('fingerprint')
            ->select('fingerprint', DB::raw('count(*) as count'), DB::raw('MAX(message) as sample_message'))
            ->groupBy('fingerprint')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $headers = ['Fingerprint', 'Count', 'Sample Message'];
        $rows = $fingerprints->map(function ($item) {
            return [
                substr($item->fingerprint, 0, 16) . '...',
                number_format($item->count),
                \Str::limit($item->sample_message, 50),
            ];
        });

        $this->table($headers, $rows);
        $this->newLine();

        // File states
        $this->info("Indexed Files:");
        $fileStates = LogFileState::orderBy('last_seen_at', 'desc')->get();

        $headers = ['File', 'Env', 'Status', 'Last Seen'];
        $rows = $fileStates->map(function ($state) {
            return [
                basename($state->path),
                $state->env,
                $state->status,
                $state->last_seen_at?->diffForHumans() ?? 'Never',
            ];
        });

        $this->table($headers, $rows);

        return self::SUCCESS;
    }
}

