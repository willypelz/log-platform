<?php

namespace Willypelz\LogPlatform\Handlers;

use Willypelz\LogPlatform\Contracts\NamingStrategyInterface;
use Willypelz\LogPlatform\Services\StrategyManager;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\LogRecord;

class StrategyRotatingFileHandler extends StreamHandler
{
    protected string $basePath;
    protected string $channel;
    protected NamingStrategyInterface $strategy;
    protected ?\DateTimeInterface $lastWriteTime = null;
    protected ?string $currentFilename = null;

    public function __construct(
        string $basePath,
        string $channel,
        NamingStrategyInterface $strategy,
        int|string|Level $level = Level::Debug,
        bool $bubble = true
    ) {
        $this->basePath = rtrim($basePath, '/');
        $this->channel = $channel;
        $this->strategy = $strategy;

        // Initialize with current filename
        $this->rotateFile(new \DateTimeImmutable());

        parent::__construct($this->currentFilename, $level, $bubble);
    }

    /**
     * Create handler from array config.
     */
    public static function fromConfig(array $config): self
    {
        $strategyManager = app(StrategyManager::class);

        $strategyName = $config['strategy'] ?? 'daily';
        $strategyOptions = $config['strategy_options'] ?? [];

        $strategy = $strategyManager->resolve($strategyName, $strategyOptions);

        return new self(
            $config['path'],
            $config['channel'] ?? 'laravel',
            $strategy,
            $config['level'] ?? Level::Debug,
            $config['bubble'] ?? true
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function write(LogRecord $record): void
    {
        $now = new \DateTimeImmutable();

        // Check if we need to rotate
        if ($this->lastWriteTime && $this->strategy->shouldRotate($this->lastWriteTime, $now)) {
            $this->rotateFile($now);
        }

        $this->lastWriteTime = $now;

        parent::write($record);

        // Emit event for streaming (if enabled)
        $this->emitStreamEvent($record);
    }

    /**
     * Rotate to a new log file.
     */
    protected function rotateFile(\DateTimeInterface $date): void
    {
        $filename = $this->strategy->resolveFilename($date, $this->channel);
        $this->currentFilename = $this->basePath . '/' . $filename;

        // Close current stream if open
        if (is_resource($this->stream)) {
            fclose($this->stream);
            $this->stream = null;
        }

        // Update the URL (StreamHandler will open it on next write)
        $this->url = $this->currentFilename;
    }

    /**
     * Emit stream event for real-time updates.
     */
    protected function emitStreamEvent(LogRecord $record): void
    {
        try {
            if (config('log-platform.streaming.enabled', false)) {
                event(new \Willypelz\LogPlatform\Events\LogWritten([
                    'level' => $record->level->getName(),
                    'message' => $record->message,
                    'context' => $record->context,
                    'datetime' => $record->datetime->format('Y-m-d H:i:s'),
                    'channel' => $this->channel,
                ]));
            }
        } catch (\Throwable $e) {
            // Silently fail to avoid breaking logging
        }
    }
}

