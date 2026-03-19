<?php

namespace Willypelz\LogPlatform\Processors;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class EnvironmentProcessor implements ProcessorInterface
{
    protected string $environment;

    public function __construct(?string $environment = null)
    {
        $this->environment = $environment ?? app()->environment();
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra['env'] = $this->environment;

        return $record;
    }
}

