<?php

namespace Willypelz\LogPlatform\Contracts;

interface QueryEngineInterface
{
    /**
     * Execute a structured query against indexed logs.
     *
     * @param string $query
     * @param array $filters
     * @param int $limit
     * @param string|null $cursor
     * @return array{data: array, cursor: ?string, hasMore: bool}
     */
    public function query(string $query, array $filters = [], int $limit = 100, ?string $cursor = null): array;

    /**
     * Get logs by request ID.
     *
     * @param string $requestId
     * @return array
     */
    public function getByRequestId(string $requestId): array;

    /**
     * Get logs by fingerprint.
     *
     * @param string $fingerprint
     * @param int $limit
     * @return array
     */
    public function getByFingerprint(string $fingerprint, int $limit = 100): array;
}

