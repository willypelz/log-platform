<?php

namespace Willypelz\LogPlatform\LogTypes;

class PostgresLogType extends LogType
{
    public function pattern(): string
    {
        // Postgres log format
        return '/^(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})\.\d+\s\w+\s\[(\d+)\]\s(\w+):\s+(.*)$/';
    }

    public function levelClass(): string
    {
        return \Willypelz\LogPlatform\Services\LogParser::class;
    }
}

