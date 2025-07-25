<?php

declare(strict_types=1);

namespace Bono\Factory;

use Bono\Agent\CoderAgent;
use Bono\Provider\LlmProviderInterface;

class CoderAgentFactory
{
    public function __construct(private LlmProviderInterface $provider)
    {
        // Konstruktor kann leer bleiben, da keine Initialisierung nötig ist
    }

    public function __invoke(): CoderAgent
    {
        return new CoderAgent($this->provider);
    }
}
