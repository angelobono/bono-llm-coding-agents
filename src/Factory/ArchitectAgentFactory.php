<?php

declare(strict_types=1);

namespace Bono\Factory;

use Bono\Agent\ArchitectAgent;
use Bono\Api\LlmProviderInterface;

/**
 * Factory for creating instances of ArchitectAgent.
 * This factory is responsible for creating instances of the ArchitectAgent
 * with the specified LLM provider and model configurations.
 */
final class ArchitectAgentFactory
{
    public function __construct(
        private readonly LlmProviderInterface $provider,
        private readonly string $analysisModel = 'llama3.2:3b',
        private readonly string $generationModel = 'llama3.2:3b'
    ) {}

    public function __invoke(): ArchitectAgent
    {
        return new ArchitectAgent(
            $this->provider,
            $this->analysisModel,
            $this->generationModel
        );
    }
}
