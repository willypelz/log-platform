<?php

namespace Willypelz\LogPlatform\Services;

class HostManager
{
    protected array $hosts = [];

    public function __construct()
    {
        $this->loadHostsFromConfig();
    }

    /**
     * Load hosts from configuration.
     */
    protected function loadHostsFromConfig(): void
    {
        $hosts = config('log-platform.hosts', []);

        foreach ($hosts as $identifier => $config) {
            $this->hosts[$identifier] = [
                'identifier' => $identifier,
                'name' => $config['name'] ?? $identifier,
                'host' => $config['host'] ?? null,
                'path' => $config['path'] ?? storage_path('logs'),
                'auth' => $config['auth'] ?? null,
                'headers' => $config['headers'] ?? [],
                'is_remote' => !empty($config['host']),
            ];
        }
    }

    /**
     * Get all configured hosts.
     */
    public function all(): array
    {
        return $this->hosts;
    }

    /**
     * Get a specific host by identifier.
     */
    public function get(string $identifier): ?array
    {
        return $this->hosts[$identifier] ?? null;
    }

    /**
     * Get local host.
     */
    public function getLocal(): ?array
    {
        foreach ($this->hosts as $host) {
            if (!$host['is_remote']) {
                return $host;
            }
        }

        return null;
    }

    /**
     * Get all remote hosts.
     */
    public function getRemote(): array
    {
        return array_filter($this->hosts, fn($host) => $host['is_remote']);
    }

    /**
     * Fetch logs from remote host.
     */
    public function fetchRemoteLogs(string $identifier, array $params = []): array
    {
        $host = $this->get($identifier);

        if (!$host || !$host['is_remote']) {
            throw new \InvalidArgumentException("Host {$identifier} not found or not remote");
        }

        $client = new \GuzzleHttp\Client([
            'base_uri' => $host['host'],
            'timeout' => 30,
            'headers' => array_merge([
                'Accept' => 'application/json',
            ], $host['headers'] ?? []),
            'auth' => $host['auth'],
        ]);

        try {
            $response = $client->get('/log-platform/api/logs', [
                'query' => $params,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            return [
                'error' => 'Failed to fetch remote logs: ' . $e->getMessage(),
                'data' => [],
            ];
        }
    }
}

