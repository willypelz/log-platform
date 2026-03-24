<?php

namespace Willypelz\LogPlatform\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamController extends Controller
{
    /**
     * Tail a log file via Server-Sent Events (filesystem only).
     *
     * Query params:
     *   file  – filename relative to storage/logs (default: laravel.log)
     *   lines – number of initial tail lines to send (default: 50)
     */
    public function stream(Request $request): StreamedResponse
    {
        $filename = basename($request->input('file', 'laravel.log'));
        $filePath = storage_path('logs/' . $filename);
        $initialLines = max(1, (int) $request->input('lines', 50));

        return response()->stream(function () use ($filePath, $initialLines) {
            if (!file_exists($filePath)) {
                echo "data: " . json_encode(['error' => 'File not found: ' . basename($filePath)]) . "\n\n";
                ob_flush();
                flush();
                return;
            }

            // Send initial tail lines
            $lines = $this->tail($filePath, $initialLines);
            foreach ($lines as $line) {
                if (trim($line) === '') continue;
                echo "data: " . json_encode(['line' => $line, 'file' => basename($filePath)]) . "\n\n";
            }
            ob_flush();
            flush();

            // Watch for new content
            $lastSize = filesize($filePath);

            while (true) {
                if (connection_aborted()) break;

                clearstatcache(true, $filePath);
                $currentSize = filesize($filePath);

                if ($currentSize > $lastSize) {
                    $handle = fopen($filePath, 'r');
                    fseek($handle, $lastSize);
                    while (!feof($handle)) {
                        $line = fgets($handle);
                        if ($line !== false && trim($line) !== '') {
                            echo "data: " . json_encode(['line' => rtrim($line), 'file' => basename($filePath)]) . "\n\n";
                            ob_flush();
                            flush();
                        }
                    }
                    fclose($handle);
                    $lastSize = $currentSize;
                } elseif ($currentSize < $lastSize) {
                    // File was rotated/truncated
                    $lastSize = 0;
                    echo "data: " . json_encode(['event' => 'rotated', 'file' => basename($filePath)]) . "\n\n";
                    ob_flush();
                    flush();
                }

                sleep(1);
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    /**
     * Return the last N lines of a file.
     */
    private function tail(string $path, int $lines): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) return [];

        $buffer = '';
        $chunkSize = 4096;
        fseek($handle, 0, SEEK_END);
        $fileSize = ftell($handle);
        $collected = 0;
        $pos = $fileSize;

        while ($collected <= $lines && $pos > 0) {
            $readSize = min($chunkSize, $pos);
            $pos -= $readSize;
            fseek($handle, $pos);
            $buffer = fread($handle, $readSize) . $buffer;
            $collected = substr_count($buffer, "\n");
        }

        fclose($handle);

        $allLines = explode("\n", $buffer);
        return array_slice($allLines, -$lines);
    }
}
