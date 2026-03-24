<?php

namespace Willypelz\LogPlatform\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Willypelz\LogPlatform\Services\FileOnlyLogReader;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FilesController extends Controller
{
    public function __construct(protected FileOnlyLogReader $reader) {}

    /**
     * List all .log files in storage/logs (and any configured additional folders).
     */
    public function index(Request $request): JsonResponse
    {
        $basePath = storage_path('logs');
        $folders  = array_merge(
            [$basePath],
            array_values(config('log-platform.additional_folders', []))
        );

        $fileList = [];
        foreach ($folders as $folder) {
            if (!is_dir($folder)) continue;
            foreach (File::files($folder) as $file) {
                if (strtolower($file->getExtension()) !== 'log') continue;
                $size = $file->getSize();
                $fileList[] = [
                    'name'          => $file->getFilename(),
                    'size'          => $size,
                    'size_human'    => $this->formatBytes($size),
                    'modified'      => $file->getMTime(),
                    'modified_human'=> date('Y-m-d H:i:s', $file->getMTime()),
                ];
            }
        }

        usort($fileList, fn($a, $b) => $b['modified'] <=> $a['modified']);

        return response()->json([
            'files' => $fileList,
            'count' => count($fileList),
        ]);
    }

    /**
     * Get metadata for a single log file.
     */
    public function show(string $filename): JsonResponse
    {
        $filePath = storage_path('logs/' . basename($filename));

        if (!File::exists($filePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $size     = filesize($filePath);
        $modified = filemtime($filePath);

        return response()->json([
            'name'          => basename($filePath),
            'size'          => $size,
            'size_human'    => $this->formatBytes($size),
            'modified'      => $modified,
            'modified_human'=> date('Y-m-d H:i:s', $modified),
        ]);
    }

    /**
     * Read parsed log entries from a file with optional filters.
     *
     * Query params:
     *   file      – filename (relative to storage/logs)
     *   level     – filter by level
     *   keyword   – filter by keyword in message
     *   from      – date-time lower bound
     *   to        – date-time upper bound
     *   limit     – max entries (default 100, max 500)
     */
    public function logs(Request $request): JsonResponse
    {
        $filename = basename($request->input('file', 'laravel.log'));
        $filePath = storage_path('logs/' . $filename);

        if (!File::exists($filePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $limit   = min((int) $request->input('limit', 100), 500);
        $filters = array_filter([
            'level'   => $request->input('level'),
            'keyword' => $request->input('keyword'),
            'from'    => $request->input('from'),
            'to'      => $request->input('to'),
        ]);

        $entries = $this->reader->readFile($filePath, $filters, $limit);

        return response()->json([
            'file'    => $filename,
            'count'   => count($entries),
            'entries' => $entries,
        ]);
    }

    /**
     * Read raw lines from a log file (paginated).
     *
     * Query params:
     *   file  – filename (relative to storage/logs)
     *   page  – page number (1-based, default 1)
     *   per_page – lines per page (default 200, max 1000)
     */
    public function contents(Request $request): JsonResponse
    {
        $filename = basename($request->input('file', 'laravel.log'));
        $filePath = storage_path('logs/' . $filename);

        if (!File::exists($filePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $perPage  = min((int) $request->input('per_page', 200), 1000);
        $page     = max(1, (int) $request->input('page', 1));
        $offset   = ($page - 1) * $perPage;

        $allLines   = file($filePath, FILE_IGNORE_NEW_LINES);
        $totalLines = count($allLines);
        $lines      = array_slice($allLines, $offset, $perPage);

        return response()->json([
            'file'        => $filename,
            'total_lines' => $totalLines,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int) ceil($totalLines / $perPage),
            'lines'       => $lines,
        ]);
    }

    /**
     * Download a log file.
     */
    public function download(Request $request): BinaryFileResponse
    {
        $validated = $request->validate(['file' => 'required|string']);
        $filePath  = storage_path('logs/' . basename($validated['file']));

        if (!File::exists($filePath)) {
            abort(404, 'File not found');
        }

        return response()->download($filePath);
    }

    /**
     * Delete a log file.
     */
    public function delete(Request $request): JsonResponse
    {
        $validated = $request->validate(['file' => 'required|string']);
        $filePath  = storage_path('logs/' . basename($validated['file']));

        if (!File::exists($filePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        File::delete($filePath);

        return response()->json([
            'message' => 'File deleted successfully',
            'file'    => basename($validated['file']),
        ]);
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

