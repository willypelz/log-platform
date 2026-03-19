<?php

namespace Willypelz\LogPlatform\Http\Controllers\Api;

use Willypelz\LogPlatform\Models\AlertRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AlertsController extends Controller
{
    /**
     * List alert rules.
     */
    public function index(): JsonResponse
    {
        // Alerts require database indexing
        if (!config('log-platform.indexing.enabled')) {
            return response()->json([
                'error' => 'Alerts are only available with database indexing enabled.',
                'message' => 'Enable indexing in config/log-platform.php to use this feature.',
            ], 400);
        }

        $rules = AlertRule::with('events')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($rules);
    }

    /**
     * Create alert rule.
     */
    public function store(Request $request): JsonResponse
    {
        // Alerts require database indexing
        if (!config('log-platform.indexing.enabled')) {
            return response()->json([
                'error' => 'Alerts are only available with database indexing enabled.',
                'message' => 'Enable indexing in config/log-platform.php to use this feature.',
            ], 400);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'query' => 'required|string',
            'window_seconds' => 'required|integer|min:1',
            'threshold_count' => 'required|integer|min:1',
            'channels' => 'required|array',
            'channels.*' => 'string|in:mail,slack,webhook',
            'cooldown_seconds' => 'nullable|integer|min:0',
            'enabled' => 'boolean',
        ]);

        $rule = AlertRule::create($validated);

        return response()->json($rule, 201);
    }

    /**
     * Update alert rule.
     */
    public function update(Request $request, AlertRule $rule): JsonResponse
    {
        // Alerts require database indexing
        if (!config('log-platform.indexing.enabled')) {
            return response()->json([
                'error' => 'Alerts are only available with database indexing enabled.',
                'message' => 'Enable indexing in config/log-platform.php to use this feature.',
            ], 400);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'query' => 'sometimes|string',
            'window_seconds' => 'sometimes|integer|min:1',
            'threshold_count' => 'sometimes|integer|min:1',
            'channels' => 'sometimes|array',
            'channels.*' => 'string|in:mail,slack,webhook',
            'cooldown_seconds' => 'nullable|integer|min:0',
            'enabled' => 'boolean',
        ]);

        $rule->update($validated);

        return response()->json($rule);
    }

    /**
     * Delete alert rule.
     */
    public function destroy(AlertRule $rule): JsonResponse
    {
        // Alerts require database indexing
        if (!config('log-platform.indexing.enabled')) {
            return response()->json([
                'error' => 'Alerts are only available with database indexing enabled.',
                'message' => 'Enable indexing in config/log-platform.php to use this feature.',
            ], 400);
        }

        $rule->delete();

        return response()->json(null, 204);
    }
}

