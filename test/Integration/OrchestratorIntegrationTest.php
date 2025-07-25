<?php

declare(strict_types=1);

namespace Bono\Tests\Integration;

use Bono\Cache\FileCache;
use Bono\Agent\Orchestrator;
use PHPUnit\Framework\TestCase;
use Bono\Cache\CachingDecorator;
use Bono\Provider\OllamaProvider;
use Bono\Factory\CoderAgentFactory;
use PHPUnit\Framework\Attributes\Test;
use Bono\Factory\ArchitectAgentFactory;
use Bono\Tests\Mock\StableDiffusionMock;

use function implode;
use function array_keys;

/**
 * Multi-Agent Integration Test
 *
 * simuliert die Zusammenarbeit von Architekt- und Coder-Agenten
 * bei der Entwicklung eines Dashboards mit Patientenakte, sowie die Nutzung
 * eines Caching-Mechanismus.
 */
class OrchestratorIntegrationTest extends TestCase
{
    #[Test]
    public function fullTaskWithRealOllama()
    {
        // Orchestrator initialisieren
        $orchestrator = $this->getOrchestrator();

        // Task ausführen
        $result = $orchestrator->processTask(<<<USER_STORY
As a doctor, I want a dashboard with patient records.
            
Acceptance criteria
- The API provides an endpoint to list patient records with key information (GET /api/patients returns name, ID, diagnosis).
- The API supports searching and filtering patient records by name or ID via query parameters (GET /api/patients?name=...&id=...).
- The API provides an endpoint to retrieve detailed information for a single patient (GET /api/patients/{id}).
- Access to all patient endpoints requires authentication (e.g., JWT token).
- The API responses are structured in JSON and support clients on desktop and tablet.

Not acceptance criteria
- The API does not provide endpoints to edit or delete patient records (PUT, DELETE are not available).
- The API does not provide endpoints for analytics or statistics.
- The API does not provide endpoints to export patient data (e.g., no CSV/PDF export).
- The API does not send notifications for new records.
- The API does not connect or synchronize with external hospital systems.
USER_STORY);

        // 7) Assertions
        $this->assertTaskResultIsValid($result);

        // Optional → Logging ausgeben
        echo "\nAnalyse-Komplexität: " . $result->analysis->complexity;
        echo "\nGenerierte Dateien: " . implode(', ', array_keys($result->files));
    }

    #[Test]
    public function cachedTask()
    {
        // Orchestrator initialisieren
        $orchestrator = $this->getOrchestrator();

        // 3.1) Cache und CachingDecorator initialisieren
        $cache = new FileCache();

        /** @var Orchestrator $cached */
        $cached = new CachingDecorator($orchestrator, $cache);

        // 4) StableDiffusion MOCK registrieren (kein echter API-Call)
        $cached->registerTool('stable_diffusion', new StableDiffusionMock());

        // 5) Test-UserStory
        $userStory = 'As a doctor, I want a dashboard with patient records.';

        // 6) Task ausführen
        $result = $cached->processTask($userStory);

        // 7) Assertions
        $this->assertTaskResultIsValid($result);

        // Optional → Logging ausgeben
        echo "\nAnalyse-Komplexität: " . $result->analysis->complexity;
        echo "\nGenerierte Dateien: " . implode(', ', array_keys($result->files));
    }

    public function getOrchestrator(): Orchestrator
    {
        // 1) REALER Provider → nutzt dein lokales Ollama
        $ollama = new OllamaProvider('http://localhost:11434/api');

        // 2) Agenten aus Factory
        $architect = (new ArchitectAgentFactory($ollama))->__invoke();
        $coder = (new CoderAgentFactory($ollama, 'qwen2.5-coder:3b'))->__invoke();

        // 3) Orchestrator bauen
        $orchestrator = new Orchestrator($architect, $coder);
        $orchestrator->registerTool('stable_diffusion', new StableDiffusionMock());
        return $orchestrator;
    }

    public function assertTaskResultIsValid(\Bono\Data\TaskResult $result): void
    {
        $this->assertTrue($result->success, 'Task sollte erfolgreich sein');
        $this->assertNotEmpty(
            $result->files, 'Es sollte mindestens eine Datei geben'
        );
        $this->assertNotEmpty(
            $result->analysis->getRequirements(),
            'Analyse sollte Requirements enthalten'
        );
        $this->assertNotSame(
            'unknown', $result->analysis->complexity,
            'Komplexität darf nicht unknown bleiben'
        );
        $this->assertNotEmpty(
            $result->analysis->architecture,
            'Analyse sollte eine Architektur enthalten'
        );
    }
}
