<?php

namespace Willypelz\LogPlatform\Jobs;

use Willypelz\LogPlatform\Services\LogIndexer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IndexLogChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        protected string $filePath,
        protected string $env,
        protected string $channel
    ) {
    }

    public function handle(LogIndexer $indexer): void
    {
        $indexer->indexFile($this->filePath, $this->env, $this->channel);
    }
}

