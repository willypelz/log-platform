<?php

namespace Willypelz\LogPlatform\LogTypes;

class RedisLogType extends LogType
{
    public function pattern(): string
    {
        // Redis log format
        return '/^(\d+):([MSCX])\s(\d+\s\w+\s\d{4}\s\d{2}:\d{2}:\d{2}\.\d+)\s([*#.\-])\s(.*)$/';
    }

    public function levelClass(): string
    {
        return \Willypelz\LogPlatform\Services\LogParser::class;
    }
}

