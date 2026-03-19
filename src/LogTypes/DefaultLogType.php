<?php

namespace Willypelz\LogPlatform\LogTypes;

class DefaultLogType extends LogType
{
    public function pattern(): string
    {
        // Laravel standard log format
        return '/^\[(\d{4}-\d{2}-\d{2}[T\s]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[+-]\d{2}:\d{2})?)\]\s+(\w+)\.(\w+):/';
    }

    public function levelClass(): string
    {
        return \Willypelz\LogPlatform\Services\LogParser::class;
    }
}

