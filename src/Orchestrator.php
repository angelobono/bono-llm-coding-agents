<?php

declare(strict_types=1);

namespace Bono;

use Bono\Agent\ArchitectAgent;
use Bono\Agent\CoderAgent;
use Bono\Cache\ArrayCache;
use Bono\Data\TaskResult;
use Bono\Data\UserStoryAnalysis;
use Bono\Factory\LoggerFactory;
use Bono\Parser\LlmResponseParser;
use Bono\Tool\Tool;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

use function array_merge;
use function array_unique;
use function array_values;
use function basename;
use function count;
use function escapeshellarg;
use function file_get_contents;
use function file_put_contents;
use function glob;
use function implode;
use function is_array;
use function is_dir;
use function is_file;
use function json_encode;
use function md5;
use function mkdir;
use function preg_match;
use function preg_match_all;
use function rmdir;
use function shell_exec;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function strtolower;
use function trim;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const PHP_EOL;

/**
 * Orchestrator-Klasse, die die Interaktion zwischen Architekt und Coder steuert
 * und Tool-Aufrufe verwaltet.
 */
class Orchestrator implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private CacheInterface $cache;
    private ArchitectAgent $architekt;
    private CoderAgent $coder;
    private array $tools = [];

    public function __construct(
        ArchitectAgent $architekt,
        CoderAgent $coder,
        ?CacheInterface $cache = null
    ) {
        $this->cache     = $cache ?? new ArrayCache();
        $this->architekt = $architekt;
        $this->coder     = $coder;

        if (! $this->logger) {
            $this->logger = (new LoggerFactory(self::class))->__invoke();
        }
    }

    private function getCachedPlan(string $response): ?array
    {
        $key = 'plan_' . md5($response);
        return $this->cache->get($key);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function cachePlan(string $response, array $plan): void
    {
        $key = 'plan_' . md5($response);
        $this->cache->set($key, $plan, 300); // cache 5min
    }

    public function registerTool(string $name, Tool $tool): void
    {
        $this->logger->info("Tool registriert", ['tool' => $name]);
        $this->tools[strtolower($name)] = $tool;
    }

    public function processTask(string $userStory): TaskResult
    {
        $result = new TaskResult($userStory);

        $this->clearGeneratedFiles($result->getId());

        $this->logger->info("=== ANALYSE-PHASE ===");
        $result->analysis = $this->architekt->analyseUserStory($userStory);

        $this->logger->info("=== PLANUNGSPHASE ===");
        $plan            = $this->architekt->createImplementationPlan($result->analysis);
        $result->success = ! empty($plan['files']);
        $result->message = $result->success ? "Planung erfolgreich" : "Keine Dateien geplant";

        $this->logger->info("Planung abgeschlossen", [
            'success'     => $result->success,
            'files_count' => count($plan['files'] ?? []),
        ]);

        if (! $result->success) {
            $result->validation = "Keine Dateien geplant. Bitte User Story überprüfen.";
            return $result;
        }

        $this->logger->debug("Geplante Dateien: " . implode(', ', $plan['files']));
        $generatedFiles = [];
        $lastResponse   = null;

        foreach ($plan['files'] as $fileName) {
            $this->logger->info("=== CODE-GENERIERUNG für Datei: {$fileName} ===");

            $toolResult = null;
            $maxRounds  = 10;
            $round      = 0;

            while ($round < $maxRounds) {
                $round++;

                if ($toolResult !== null) {
                    $this->coder->injectToolResult($toolResult);
                    $toolResult = null;
                }

                $response = trim($this->coder->generateCode(
                    $this->buildCoderPrompt($result->analysis, $fileName)
                ));

                $lastResponse = $response; // Merke die letzte Antwort

                $this->logger->debug("[Coder-Response]: {$response}");

                if ($this->isToolCall($response)) {
                    $decoded = LlmResponseParser::parseJson($response);

                    if ($decoded && isset($decoded['tool'], $decoded['param'])) {
                        $toolName = strtolower($decoded['tool']);
                        $param    = $decoded['param'];
                        $this->logger->info("[System] Tool-Aufruf erkannt: {$toolName} mit Param '{$param}'");

                        if (isset($this->tools[$toolName])) {
                            $toolResult = $this->tools[$toolName]->execute($param);
                            $this->logger->info("[Tool-Ergebnis]: {$toolResult}");
                        } else {
                            $this->logger->warning("Unbekanntes Tool: {$toolName}");
                            break;
                        }
                        continue;
                    }
                }

                if (! LlmResponseParser::containsCode($response)) {
                    $this->logger->warning("[Coder liefert keinen Code] – Weiterleitung an Architekt ...");

                    $planUpdate = $this->architekt->createImplementationPlanFromCoderFeedback($response);

                    if (empty($planUpdate)) {
                        $this->logger->error("[Architekt konnte keine zusätzlichen Infos liefern]");
                        break;
                    }
                    $plan = $this->mergePlan($plan, $planUpdate);
                    $result->analysis->setRequirements($plan['requirements'] ?? []);
                    $result->analysis->setEntities($plan['entities'] ?? []);
                    $result->analysis->setActions($plan['actions'] ?? []);
                    $result->analysis->setComplexity($plan['complexity'] ?? 'unknown');
                    $result->analysis->setArchitecture($plan['architecture'] ?? 'unknown');
                    $result->message = "Plan aktualisiert, weitere Dateien geplant: " . implode(', ', $plan['files']);

                    continue;
                }

                $savedPath                 = $this->saveGeneratedFile(
                    $result->getId(),
                    $fileName,
                    LlmResponseParser::parsePhp($response)
                );
                $generatedFiles[$fileName] = $savedPath;
                break;
            }
        }

        $result->files = $generatedFiles;

        $this->logger->info("=== CODE-GENERIERUNG für Datei: composer.json ===");
        $this->generateComposerJson($result);
        return $result;
    }

    private function isToolCall(string $response): bool
    {
        if (! str_contains($response, '"tool"')) {
            return false;
        }
        $decoded = LlmResponseParser::parseJson($response);
        return $decoded && isset($decoded['tool']) && isset($decoded['param']);
    }

    /**
     * Merged zwei Pläne, sodass alte Dateien/Requirements erhalten bleiben
     */
    private function mergePlan(array $originalPlan, array $newPlan): array
    {
        return [
            'requirements' => array_values(array_unique(array_merge(
                $originalPlan['requirements'] ?? [],
                $newPlan['requirements'] ?? []
            ))),
            'entities'     => array_values(array_unique(array_merge(
                $originalPlan['entities'] ?? [],
                $newPlan['entities'] ?? []
            ))),
            'actions'      => array_values(array_unique(array_merge(
                $originalPlan['actions'] ?? [],
                $newPlan['actions'] ?? []
            ))),
            'files'        => array_merge(
                $originalPlan['files'] ?? [],
                $newPlan['files'] ?? []
            ),
            'complexity'   => $newPlan['complexity']
                ?? $originalPlan['complexity']
                    ?? 'unknown',
            'architecture' => $newPlan['architecture']
                ?? $originalPlan['architecture']
                    ?? 'unknown',
        ];
    }

    private function saveGeneratedFile(
        string $path,
        string $fileName,
        string $content
    ): string {
        $targetDir = __DIR__ . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . 'generated' . DIRECTORY_SEPARATOR
            . $path . DIRECTORY_SEPARATOR
            . 'src';

        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        if (str_ends_with($fileName, '.php')) {
            $namespace = $this->getPhpNamespaceAsPathFromContent($content);

            if ($namespace !== '') {
                $targetDir .= DIRECTORY_SEPARATOR . $namespace;

                if (! is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                $fileName = basename($fileName); // Nur den Dateinamen behalten
            }
        }

        $fullPath = $targetDir . DIRECTORY_SEPARATOR . $fileName;
        file_put_contents($fullPath, $content);

        // Optional: PHP-Lint Check
        if (str_ends_with($fileName, '.php')) {
            $lintResult = shell_exec("php -l " . escapeshellarg($fullPath));
            $this->logger->debug("[Lint] " . trim($lintResult));
        }
        return $fullPath;
    }

    private function clearGeneratedFiles(string $id): void
    {
        $targetDir = __DIR__ . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . 'generated' . DIRECTORY_SEPARATOR . $id;

        if (is_dir($targetDir)) {
            $files = glob($targetDir . '/*'); // Alle Dateien im Verzeichnis
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file); // Datei löschen
                }
            }
            rmdir($targetDir); // Verzeichnis löschen, wenn leer
        }
    }

    private function getPhpNamespaceAsPathFromContent(string $content): string
    {
        // Suche nach dem Namespace im PHP-Code
        if (preg_match('/namespace\s+([a-zA-Z0-9_\\\\]+);/', $content, $matches)) {
            $namespace = trim($matches[1]);
            // Ersetze Backslashes durch Verzeichnistrenner
            return str_replace('\\', DIRECTORY_SEPARATOR, $namespace);
        }
        return ''; // Kein Namespace gefunden
    }

    private function buildCoderPrompt(
        UserStoryAnalysis $analysis,
        string $fileName
    ): string {
        // Fallbacks aus Analyse, falls im Plan leer
        $requirements = json_encode($analysis->getRequirements(), JSON_PRETTY_PRINT);
        $entities     = json_encode($analysis->getEntities(), JSON_PRETTY_PRINT);
        $actions      = json_encode($analysis->getActions(), JSON_PRETTY_PRINT);

        return <<<PROMPT
Du bist ein PHP-Coder. Du verwendest Standards wie PSR und ITEM-drafts und Best
Practices. Deine bevorzugtenLibraries/PHP-Extensions sind Mezzio, Laminas, Doctrine and Monolog.
Never use PHP-Closing-Tags in .php files. Hier ist das Projekt in kompakter 
Form:

Requirements: {$requirements}
Entities: {$entities}
Actions: {$actions}
Komplexität: {$analysis->getComplexity()}
Architektur: {$analysis->getArchitecture()}

Deine Aufgabe: Generiere NUR die Datei **{$fileName}**.

WICHTIG:
- Verwende nur Englisch
- Gib nur den reinen PHP-Code zurück
- Maximale Zeilenlänge: 80 Zeichen
- Verwende PHP 8.2+ Features, wo sinnvoll
- Verwende immer opening PHP-Tags `<?php` und schließe sie nicht ab
- Keine Erklärungen, keine zusätzlichen Texte
- Fertig = wenn du diese eine Datei abgeschlossen hast
- Verwende PHP Generics (@template T), wo sinnvoll
- sei kompatibel mit phpstan und psalm

PROMPT;
    }

    private function generateComposerJson(TaskResult $result): void
    {
        $targetDir = __DIR__ . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . 'generated' . DIRECTORY_SEPARATOR
            . $result->getId() . DIRECTORY_SEPARATOR;

        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $useStatements = $this->getUseStatementsFromAnalysis($result->files);
        $prompt        = <<<PROMPT
Du bist ein PHP-Coder. Deine Aufgabe ist es, eine gültige `composer.json` für
 ein PHP-Projekt zu erstellen. Verwende die folgenden `use`-Statements, um die
abhängigen Pakete zu identifizieren und füge sie unter `"require"` hinzu:  
{$useStatements}

```json
{
    "name": "angelobono/bono-generated",
    "description": "Ein Projekt generiert von angelobono/bono",
    "type": "library",
    "require": {
        "php": "^8.2",
        "ext-json": "*"
    },
    "autoload": {
        "psr-4": {
            "App\\\\": "src/"
        }
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0",
        "phpstan/phpstan": "^1.10",
        "squizlabs/php_codesniffer": "^3.7",
        "vimeo/psalm": "^5.0"
    }
}
```

Wichtig:
- Gib ausschließlich die fertige `composer.json` zurück. Keine Erklärungen, 
keine Kommentare.
- Verändere die Struktur nicht, sondern erweitere nur die `"require"`-Sektion
- Verwende die aktuellsten stabilen Versionen der Pakete
- Achte darauf, dass die `composer.json` gültig ist und alle Abhängigkeiten
  korrekt aufgelistet sind.

PROMPT;
        $response      = $this->coder->generateCode($prompt, true);
        /*
        if ($analysis) {
            $composerData['extra'] = [
                'bono_analysis' => [
                    'requirements' => $analysis->getRequirements(),
                 \\   'entities' => $analysis->getEntities(),
                    'actions' => $analysis->getActions(),
                    'complexity' => $analysis->getComplexity(),
                    'architecture' => $analysis->getArchitecture()
                ]
            ];
        }
        */
        $result->files['composer.json'] = $targetDir . 'composer.json';
        file_put_contents(
            $targetDir . 'composer.json',
            is_array($parsed = LlmResponseParser::parseJson($response))
                ? json_encode($parsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                : $parsed
        );
    }

    private function getUseStatementsFromAnalysis(array $files): string
    {
        $useStatements = [];
        foreach ($files as $fileName => $filePath) {
            if (is_file($filePath)) {
                $content = file_get_contents($filePath);

                if (preg_match_all('/use\s+([a-zA-Z0-9_\\\\]+);/', $content, $matches)) {
                    foreach ($matches[1] as $use) {
                        $useStatements[] = $use;
                    }
                }
            }
        }
        return implode(', ', array_unique($useStatements));
    }
}
