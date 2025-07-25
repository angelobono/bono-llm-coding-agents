<?php

declare(strict_types=1);

namespace Bono\Factory;

use Bono\Agent\CoderAgent;
use Bono\Api\LlmProviderInterface;

/**
 * Factory for creating instances of CoderAgent.
 * This factory is responsible for instantiating the CoderAgent with the
 * specified LLM provider and coding model.
 */
final class CoderAgentFactory
{
    public function __construct(
        private readonly LlmProviderInterface $provider,
        private readonly string $codingModel = 'qwen2.5-coder:3b'
    ) {}

    public function __invoke(): CoderAgent
    {
        return new CoderAgent(
            $this->provider,
            $this->codingModel
        );
    }
}
