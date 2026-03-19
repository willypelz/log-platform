<?php

namespace Willypelz\LogPlatform\LogTypes;

class NginxLogType extends LogType
{
    public function pattern(): string
    {
        // Nginx error log format
        return '/^(\d{4}\/\d{2}\/\d{2}\s\d{2}:\d{2}:\d{2})\s\[(\w+)\]\s(\d+)#(\d+):\s(.*)$/';
    }

    public function levelClass(): string
    {
        return \Willypelz\LogPlatform\Services\LogParser::class;
    }
}

