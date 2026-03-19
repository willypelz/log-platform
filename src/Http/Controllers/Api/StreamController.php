<?php

namespace Willypelz\LogPlatform\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamController extends Controller
{
    /**
     * Server-Sent Events stream for real-time logs.
     */
    public function stream(Request $request): StreamedResponse
    {
        // Streaming requires database indexing
        if (!config('log-platform.indexing.enabled')) {
            return response()->stream(function () {
                echo "data: " . json_encode([
                    'error' => 'Streaming is only available with database indexing enabled.',
                    'message' => 'Enable indexing in config/log-platform.php to use this feature.',
                ]) . "\n\n";
            }, 400, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
            ]);
        }

        return response()->stream(function () use ($request) {
            // Set headers for SSE
            echo "retry: 10000\n\n";

            $lastId = 0;
            $env = $request->input('env', config('app.env'));
            $level = $request->input('level');

            while (true) {
                // Fetch new logs since last ID
                $query = \Willypelz\LogPlatform\Models\IndexedLog::where('id', '>', $lastId)
                    ->environment($env)
                    ->orderBy('id', 'asc')
                    ->limit(10);

                if ($level) {
                    $query->level($level);
                }

                $logs = $query->get();

                foreach ($logs as $log) {
                    echo "id: {$log->id}\n";
                    echo "data: " . json_encode($log->toArray()) . "\n\n";
                    $lastId = $log->id;
                    ob_flush();
                    flush();
                }

                // Check for new logs every second
                sleep(1);

                // Break if client disconnected
                if (connection_aborted()) {
                    break;
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}

