<?php

namespace Willypelz\LogPlatform\LogTypes;

class HorizonLogType extends LogType
{
    public function pattern(): string
    {
        // Horizon log format: [2026-03-18 10:30:45][jobId] message
        return '/^\[(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})\]\[([^\]]+)\]\s*(.*)$/';
    }

    public function levelClass(): string
    {
        return \Willypelz\LogPlatform\Services\LogParser::class;
    }
}

