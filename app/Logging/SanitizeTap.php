<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\Logger as MonologLogger;

/**
 * Attaches SanitizeProcessor to every handler of a channel.
 *
 * The framework reads a channel's "processors" key only for the monolog
 * driver, so single, daily, slack, syslog and errorlog would log unredacted.
 * A tap is honoured by every driver, and a handler-level processor runs after
 * the logger-level placeholder interpolation, so interpolated values are
 * redacted too.
 */
class SanitizeTap
{
    public function __invoke(Logger $logger): void
    {
        $monolog = $logger->getLogger();

        if (! $monolog instanceof MonologLogger) {
            return;
        }

        foreach ($monolog->getHandlers() as $handler) {
            if ($handler instanceof ProcessableHandlerInterface) {
                $handler->pushProcessor(new SanitizeProcessor());
            }
        }
    }
}
