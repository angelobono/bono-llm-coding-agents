<?php

declare(strict_types=1);

namespace Bono\Tests\Integration;

use Bono\Factory\ArchitectAgentFactory;
use Bono\Factory\CoderAgentFactory;
use Bono\Orchestrator;
use Bono\Provider\OllamaProvider;
use Bono\Tests\Mock\StableDiffusionMock;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function implode;

/**
 * Multi-Agent Test
 *
 * Dieser Test simuliert die Zusammenarbeit von Architekt- und Coder-Agenten
 * bei der Entwicklung eines Dashboards mit Patientenakte.
 */
class MultiAgentIntegrationTest extends TestCase
{
    #[Test]
    public function fullTaskWithRealOllama()
    {
        // 1) REALER Provider → nutzt dein lokales Ollama
        $ollama = new OllamaProvider('http://localhost:11434/api');

        // 2) Agenten aus Factory
        $architect = (new ArchitectAgentFactory($ollama))->__invoke();
        $coder     = (new CoderAgentFactory($ollama, 'llama3.2:3b'))->__invoke();

        // 3) Orchestrator bauen
        $orchestrator = new Orchestrator($architect, $coder);

        // 4) StableDiffusion MOCK registrieren (kein echter API-Call)
        $orchestrator->registerTool('stable_diffusion', new StableDiffusionMock());

        // 5) Test-UserStory
        $userStory = 'As a doctor, I want a dashboard with patient records.';

        // 6) Task ausführen
        $result = $orchestrator->processTask($userStory);

        // 7) Assertions
        $this->assertTrue($result->success, 'Task sollte erfolgreich sein');
        $this->assertNotEmpty($result->files, 'Es sollte mindestens eine Datei geben');
        $this->assertNotEmpty($result->analysis->getRequirements(), 'Analyse sollte Requirements enthalten');
        $this->assertNotSame('unknown', $result->analysis->complexity, 'Komplexität darf nicht unknown bleiben');
        $this->assertNotEmpty($result->analysis->architecture, 'Analyse sollte eine Architektur enthalten');

        // Optional → Logging ausgeben
        echo "\nAnalyse-Komplexität: " . $result->analysis->complexity;
        echo "\nGenerierte Dateien: " . implode(', ', array_keys($result->files));
    }
}
