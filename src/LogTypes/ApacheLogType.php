<?php

namespace Willypelz\LogPlatform\LogTypes;

class ApacheLogType extends LogType
{
    public function pattern(): string
    {
        // Apache error log format
        return '/^\[(\w+\s\w+\s\d+\s\d{2}:\d{2}:\d{2}\.\d+\s\d{4})\]\s\[([^\]]+)\]\s\[([^\]]+)\]\s(.*)$/';
    }

    public function levelClass(): string
    {
        return \Willypelz\LogPlatform\Services\LogParser::class;
    }
}

