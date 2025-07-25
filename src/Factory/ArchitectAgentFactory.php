<?php

declare(strict_types=1);

namespace Bono\Factory;

use Bono\Agent\ArchitectAgent;
use Bono\Provider\LlmProviderInterface;

class ArchitectAgentFactory
{
    public function __construct(private LlmProviderInterface $provider)
    {
        // Konstruktor kann leer bleiben, da keine Initialisierung nötig ist
    }

    public function __invoke(): ArchitectAgent
    {
        return new ArchitectAgent(
            $this->provider,
            'llama3.2:3b',
            'llama3.2:3b'
        );
    }
}
