<?php

declare(strict_types=1);

use Beaver\Foundation\Application;
use Beaver\Queue\Job;
use Beaver\Queue\Queue;

if (!function_exists('queue')) {
    function queue(): Queue
    {
        return Application::getInstance()->make(Queue::class);
    }
}

if (!function_exists('dispatch')) {
    /**
     * Enfileira um job para execução.
     */
    function dispatch(Job $job): void
    {
        queue()->push($job);
    }
}

if (!function_exists('dispatch_later')) {
    /**
     * Enfileira um job para execução diferida.
     */
    function dispatch_later(int $delaySeconds, Job $job): void
    {
        queue()->later($delaySeconds, $job);
    }
}
