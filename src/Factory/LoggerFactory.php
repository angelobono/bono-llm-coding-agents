<?php

declare(strict_types=1);

namespace Bono\Factory;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * LoggerFactory creates a logger instance for the application.
 *
 * This factory provides a simple way to create a logger that writes to stdout
 * with a debug level. It can be extended or modified to include additional
 * handlers or configurations as needed.
 */
class LoggerFactory
{
    public function __construct(private string $name = 'App')
    {
    }

    /**
     * Creates a logger instance.
     */
    public function __invoke(): LoggerInterface
    {
        $logger = new Logger($this->name);
        $logger->pushHandler(new StreamHandler('php://stdout', Level::Debug));
        return $logger;
    }
}
