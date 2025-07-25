<?php

declare(strict_types=1);

namespace Bono\Tests\Unit;

use Bono\Agent\Orchestrator;
use PHPUnit\Framework\TestCase;
use Bono\Factory\CoderAgentFactory;
use PHPUnit\Framework\Attributes\Test;
use Bono\Factory\ArchitectAgentFactory;
use Bono\Tests\Mock\StableDiffusionMock;
use Bono\Tests\Mock\LlmProviderInterfaceMock;

use function json_encode;

use const JSON_PRETTY_PRINT;

/**
 * Multi-Agent Test
 *
 * Dieser Test simuliert die Zusammenarbeit von Architekt- und Coder-Agenten
 * bei der Entwicklung eines Dashboards mit Patientenakte.
 */
class OrchestratorUnitTest extends TestCase
{
    #[Test]
    public function planAndDevelopADashboard()
    {
        $dummyScript = [
            // 1) Analyse
            json_encode([
                'requirements' => ['Dashboard', 'Patientenakte', 'Icon'],
                'entities'     => ['Patient', 'Dashboard', 'Icon'],
                'actions'      => ['anzeigen', 'erstellen'],
                'complexity'   => 'medium',
            ], JSON_PRETTY_PRINT),

            // 2) Plan (jetzt als JSON mit success & Dateien)
            json_encode([
                'success' => true,
                'plan'    => 'Wir erstellen ein PHP/Angular-Dashboard mit einer Patientenakte. Außerdem wird ein Icon benötigt.',
                'files'   => [
                    'Plan.txt' => "Dashboard mit Patientenakte. Icon wird benötigt.",
                ],
            ], JSON_PRETTY_PRINT),

            // 3) Tool-Aufruf
            json_encode([
                "tool"  => "stable_diffusion",
                "param" => "Modernes medizinisches Dashboard-Icon in Blau",
            ], JSON_PRETTY_PRINT),

            // 4) Finaler Code
            json_encode([
                'files' => [
                    'GeneratedCode.php' => "<?php\n// Beispielhafte PHP-Datei für das Patienten-Dashboard\n// Bild wurde eingebunden: dashboard_icon.png\n?>",
                ],
            ], JSON_PRETTY_PRINT),
        ];

        $provider = new LlmProviderInterfaceMock($dummyScript);

        $orchestrator = new Orchestrator(
            (new ArchitectAgentFactory($provider))->__invoke(),
            (new CoderAgentFactory($provider))->__invoke()
        );

        $orchestrator->registerTool('stable_diffusion', new StableDiffusionMock());

        $result = $orchestrator->processTask(
            'Als Arzt möchte ich ein Dashboard mit Patientenakte. Bitte auch ein Icon fürs Dashboard erstellen.'
        );

        $this->assertTrue($result->success);
        $this->assertArrayHasKey('GeneratedCode.php', $result->files);
        $this->assertSame('medium', $result->analysis->complexity);
        $this->assertContains('Patient', $result->analysis->entities);
    }
}
