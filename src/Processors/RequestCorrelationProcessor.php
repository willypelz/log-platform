<?php

namespace Willypelz\LogPlatform\Processors;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Illuminate\Support\Str;

class RequestCorrelationProcessor implements ProcessorInterface
{
    protected ?string $requestId = null;

    public function __invoke(LogRecord $record): LogRecord
    {
        if (!$this->requestId) {
            $this->requestId = $this->resolveRequestId();
        }

        $record->extra['request_id'] = $this->requestId;

        return $record;
    }

    protected function resolveRequestId(): string
    {
        // Try to get from request header
        if (app()->bound('request')) {
            $request = app('request');

            if ($request->hasHeader('X-Request-ID')) {
                return $request->header('X-Request-ID');
            }
        }

        // Generate new UUID
        return (string) Str::uuid();
    }

    /**
     * Reset request ID (useful for testing or long-running processes).
     */
    public function reset(): void
    {
        $this->requestId = null;
    }
}

