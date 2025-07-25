<?php

declare(strict_types=1);

namespace Bono\Tests\Mock;

use Bono\Api\Tool;
use Psr\Log\LoggerAwareTrait;
use Bono\Factory\LoggerFactory;
use Psr\Log\LoggerAwareInterface;

class StableDiffusionMock implements Tool, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct()
    {
        if (!$this->logger) {
            $this->logger = (new LoggerFactory())->__invoke();
        }
    }

    public function execute(string $param): string
    {
        $this->logger->info("[StableDiffusion] Fake-Tool generiert Bild", [
            'prompt' => $param,
        ]);
        return "MOCK_IMAGE_GENERATED dashboard_icon.png for: $param";
    }
}
