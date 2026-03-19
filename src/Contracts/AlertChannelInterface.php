<?php

namespace Willypelz\LogPlatform\Contracts;

interface AlertChannelInterface
{
    /**
     * Send an alert notification.
     *
     * @param array $alert
     * @param array $logs
     * @return bool
     */
    public function send(array $alert, array $logs): bool;

    /**
     * Get the channel name.
     *
     * @return string
     */
    public function getName(): string;
}

