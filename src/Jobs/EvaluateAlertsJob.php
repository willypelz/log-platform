<?php

namespace Willypelz\LogPlatform\Jobs;

use Willypelz\LogPlatform\Models\AlertRule;
use Willypelz\LogPlatform\Models\AlertEvent;
use Willypelz\LogPlatform\Services\LogQueryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluateAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(LogQueryService $queryService): void
    {
        $rules = AlertRule::where('enabled', true)->get();

        foreach ($rules as $rule) {
            // Skip if in cooldown
            if ($rule->isInCooldown()) {
                continue;
            }

            // Query logs within the time window
            $from = now()->subSeconds($rule->window_seconds);
            $to = now();

            $result = $queryService->query(
                $rule->query,
                ['from' => $from, 'to' => $to],
                $rule->threshold_count + 1
            );

            $matchCount = count($result['data']);

            // Check if threshold exceeded
            if ($matchCount >= $rule->threshold_count) {
                // Create alert event
                $event = AlertEvent::create([
                    'alert_rule_id' => $rule->id,
                    'triggered_at' => now(),
                    'match_count' => $matchCount,
                    'payload' => [
                        'logs' => array_slice($result['data'], 0, 10), // First 10 logs
                    ],
                ]);

                // Dispatch notifications
                $this->dispatchNotifications($rule, $event);
            }
        }
    }

    protected function dispatchNotifications(AlertRule $rule, AlertEvent $event): void
    {
        // TODO: Implement notification channels (mail, slack, webhook)
        // For now, just log
        \Log::info('Alert triggered', [
            'rule' => $rule->name,
            'count' => $event->match_count,
        ]);
    }
}

