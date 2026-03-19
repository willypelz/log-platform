<?php

namespace Willypelz\LogPlatform\Http\Controllers\Api;

use Willypelz\LogPlatform\Services\LogQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LogsController extends Controller
{
    public function __construct(protected LogQueryService $queryService)
    {
    }

    /**
     * List logs with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'nullable|string',
            'env' => 'nullable|string',
            'level' => 'nullable|string',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'keyword' => 'nullable|string',
            'request_id' => 'nullable|string',
            'fingerprint' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:500',
            'cursor' => 'nullable|string',
        ]);

        $query = $validated['query'] ?? '';
        $limit = $validated['limit'] ?? 100;
        $cursor = $validated['cursor'] ?? null;

        unset($validated['query'], $validated['limit'], $validated['cursor']);

        $result = $this->queryService->query($query, $validated, $limit, $cursor);

        return response()->json($result);
    }

    /**
     * Get single log entry.
     */
    public function show(string $id): JsonResponse
    {
        $log = \Willypelz\LogPlatform\Models\IndexedLog::findOrFail($id);

        return response()->json($log);
    }

    /**
     * Get logs by request ID.
     */
    public function byRequestId(string $requestId): JsonResponse
    {
        $logs = $this->queryService->getByRequestId($requestId);

        return response()->json([
            'request_id' => $requestId,
            'logs' => $logs,
            'count' => count($logs),
        ]);
    }

    /**
     * Get logs by fingerprint.
     */
    public function byFingerprint(Request $request, string $fingerprint): JsonResponse
    {
        $limit = $request->input('limit', 100);
        $logs = $this->queryService->getByFingerprint($fingerprint, $limit);

        return response()->json([
            'fingerprint' => $fingerprint,
            'logs' => $logs,
            'count' => count($logs),
        ]);
    }

    /**
     * Get shareable link for a log entry.
     */
    public function shareLink(string $id): JsonResponse
    {
        $log = \Willypelz\LogPlatform\Models\IndexedLog::findOrFail($id);

        $token = base64_encode(json_encode([
            'id' => $log->id,
            'signature' => hash_hmac('sha256', $log->id, config('app.key')),
            'expires' => now()->addDays(7)->timestamp,
        ]));

        $url = url("/log-platform/share/{$token}");

        return response()->json([
            'url' => $url,
            'expires_at' => now()->addDays(7)->toDateTimeString(),
        ]);
    }
}

