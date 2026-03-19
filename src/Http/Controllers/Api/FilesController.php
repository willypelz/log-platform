<?php

namespace Willypelz\LogPlatform\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FilesController extends Controller
{
    /**
     * List log files in directory.
     */
    public function index(Request $request): JsonResponse
    {
        $path = $request->input('path', storage_path('logs'));
        $env = $request->input('env', config('app.env'));

        if (!File::isDirectory($path)) {
            return response()->json(['error' => 'Directory not found'], 404);
        }

        $files = File::files($path);
        $fileList = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'log') {
                $fileList[] = [
                    'name' => $file->getFilename(),
                    'path' => $file->getPathname(),
                    'size' => $file->getSize(),
                    'size_human' => $this->formatBytes($file->getSize()),
                    'modified' => $file->getMTime(),
                    'modified_human' => date('Y-m-d H:i:s', $file->getMTime()),
                ];
            }
        }

        // Sort by modified time
        usort($fileList, fn($a, $b) => $b['modified'] <=> $a['modified']);

        return response()->json([
            'path' => $path,
            'files' => $fileList,
            'count' => count($fileList),
        ]);
    }

    /**
     * Download a log file.
     */
    public function download(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'file' => 'required|string',
        ]);

        $filePath = storage_path('logs/' . basename($validated['file']));

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
        $validated = $request->validate([
            'file' => 'required|string',
        ]);

        $filePath = storage_path('logs/' . basename($validated['file']));

        if (!File::exists($filePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        // Delete associated indexed logs
        \Willypelz\LogPlatform\Models\IndexedLog::where('source_file', $filePath)->delete();

        // Delete file state
        \Willypelz\LogPlatform\Models\LogFileState::where('path', $filePath)->delete();

        // Delete the file
        File::delete($filePath);

        return response()->json([
            'message' => 'File deleted successfully',
            'file' => basename($validated['file']),
        ]);
    }

    /**
     * Get file metadata.
     */
    public function show(string $filename): JsonResponse
    {
        $filePath = storage_path('logs/' . basename($filename));

        if (!File::exists($filePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $fileState = \Willypelz\LogPlatform\Models\LogFileState::where('path', $filePath)->first();
        $logCount = \Willypelz\LogPlatform\Models\IndexedLog::where('source_file', $filePath)->count();

        return response()->json([
            'name' => basename($filePath),
            'path' => $filePath,
            'size' => filesize($filePath),
            'size_human' => $this->formatBytes(filesize($filePath)),
            'modified' => filemtime($filePath),
            'modified_human' => date('Y-m-d H:i:s', filemtime($filePath)),
            'indexed_count' => $logCount,
            'last_indexed' => $fileState?->last_seen_at?->toDateTimeString(),
            'last_offset' => $fileState?->last_offset ?? 0,
            'status' => $fileState?->status ?? 'not_indexed',
        ]);
    }

    /**
     * Format bytes to human readable size.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

