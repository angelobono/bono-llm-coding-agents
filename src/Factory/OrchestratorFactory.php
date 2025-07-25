<?php

declare(strict_types=1);

namespace Bono\Factory;

use Bono\Agent\Orchestrator;

/**
 * OrchestratorFactory
 *
 * Diese Factory erstellt eine Instanz des Orchestrators mit den erforderlichen Agenten.
 */
final class OrchestratorFactory
{
    public function __construct(
        private readonly ArchitectAgentFactory $architektFactory,
        private readonly CoderAgentFactory $coderFactory
    ) {
    }

    public function __invoke(): Orchestrator
    {
        return new Orchestrator(
            $this->architektFactory->__invoke(),
            $this->coderFactory->__invoke()
        );
    }
}
