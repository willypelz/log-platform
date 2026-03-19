<?php

namespace Willypelz\LogPlatform\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LogWritten implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public array $log)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('log-platform.logs');
    }

    public function broadcastAs(): string
    {
        return 'log.written';
    }

    public function broadcastWith(): array
    {
        return $this->log;
    }
}

