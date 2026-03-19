<?php

namespace Willypelz\LogPlatform\LogTypes;

class PhpFpmLogType extends LogType
{
    public function pattern(): string
    {
        // PHP-FPM log format
        return '/^\[(\d{2}-\w{3}-\d{4}\s\d{2}:\d{2}:\d{2})\]\s(\w+):\s(.*)$/';
    }

    public function levelClass(): string
    {
        return \Willypelz\LogPlatform\Services\LogParser::class;
    }
}

