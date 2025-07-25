<?php

declare(strict_types=1);

namespace Bono\Agent;

use Exception;
use Psr\Log\LoggerAwareTrait;
use Bono\Factory\LoggerFactory;
use Bono\Model\UserStoryAnalysis;
use Psr\Log\LoggerAwareInterface;
use Bono\Parser\LlmResponseParser;
use Bono\Api\LlmProviderInterface;

use function in_array;
use function is_array;
use function array_map;
use function is_string;
use function json_encode;

use const JSON_PRETTY_PRINT;

/**
 * ArchitectAgent
 * This agent analyzes user stories and creates implementation plans.
 * It uses a language model to extract requirements, entities, and actions,
 * and then generates a file structure based on the analysis.
 */
final class ArchitectAgent implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Retry logic to handle empty responses from the LLM.
     * This is a static property to keep track of retries across multiple calls.
     *
     * @var array<string, int>
     */
    private static array $retryCount = [];

    /**
     * Maximum number of retries for empty responses.
     * This is a static property to limit the number of retries across multiple
     * calls.
     *
     * @var int
     */
    private static int $maxRetries = 3;

    public function __construct(
        private readonly LlmProviderInterface $provider,
        private readonly string $analysisModel = 'llama3.2:3b',
        private readonly string $planningModel = 'llama3.2:3b'
    ) {
        if (!$this->logger) {
            $this->logger = (new LoggerFactory(self::class))->__invoke();
        }
    }

    public function analyseUserStory(string $userStory): UserStoryAnalysis
    {
        $analysis = new UserStoryAnalysis($userStory);
        $prompt = <<<PROMPT
Du bist ein Software-Architekt. Beschreibe entities und actions als OpenAPI-Objekte.
Verwende nur Englisch. Analysiere folgende User Story und extrahiere:
- requirements
- entities
- actions
- complexity
- architecture

Antworte im JSON-Format:

{
  "requirements": [
    { "name": "string", "beschreibung": "string" }
  ],
  # OpenAPI-Objekte für Entities, z.B.:
  "entities": [
    { "name": "string", "beschreibung": "string", "properties": { "propertyName": { "type": "string", "description": "string" } } }
  ], 
  # OpenAPI-Objekte für Actions, z.B.:
  "actions": [
    { "name": "string", "beschreibung": "string", "method": 
    "GET|POST|PUT|DELETE", "path": "/api/example", "parameters": { "paramName": { "type": "string", "description": "string" } }, "responses": { "200": { "description": "string", "content": { "application/json": { "schema": { "\$ref": "#/components/schemas/ExampleResponse" } } } } } }
  ],
  "complexity": "low|medium|high",
  "architecture": "a comma seperated list of architectural styles, e.g. microservices, monolith, serverless, ddd, hexagonal, layered, technical, event-driven, cqrs, clean, modular, etc.",
}

User Story:
{$userStory}

Important rules:
- Use OpenAPI objects for entities and actions.
- Always include concrete field names and data types for all relevant entities.
- Use basic data models and with relevant properties and refences that are 
needed to cover the user story.
- Only create files that are necessary to implement the user story.
- Only create REST APIs that are necessary to implement the user story
- No templates, no boilerplate code or html rendering.
- Gib das Ergebnis als gültigen JSON-String zurück. Verwende keine escaped 
Slashes, sondern gebe Pfade und Namespaces mit einfachen / bzw. \ aus (wie bei json_encode(..., JSON_UNESCAPED_SLASHES) in PHP).
PROMPT;

        $response = $this->provider->generateStreamResult($prompt, [
            'model'       => $this->analysisModel,
            'temperature' => 0.1,
        ]);

        if (empty($response)) {
            if (!isset(static::$retryCount[md5($userStory)])) {
                static::$retryCount[md5($userStory)] = 0;
            }
            static::$retryCount[md5($userStory)]++;

            if (static::$retryCount[md5($userStory)] > static::$maxRetries) {
                static::$retryCount[md5($userStory)]
                    = 0; // Reset retry count after max retries
                throw new Exception(
                    'Empty response from LLM after multiple retries.'
                );
            }
            $this->logger->warning(
                '[Architekt-Analyse]: Empty response, retrying in 5 seconds...'
            );
            sleep(5);
            return $this->analyseUserStory($userStory);
        }
        if (!isset(static::$retryCount[md5($userStory)])) {
            static::$retryCount[md5($userStory)] = 0;
        }
        $decoded = LlmResponseParser::parseJson($response);
        $this->logger->debug('[Architekt-Analyse]: ', $decoded);

        if (isset($decoded['requirements'])
            && is_array(
                $decoded['requirements']
            )
        ) {
            $analysis->setRequirements(
                array_map(
                    fn($req) => $req['name'] ?? 'Unbenannt',
                    $decoded['requirements']
                )
            );
        }
        if (isset($decoded['entities']) && is_array($decoded['entities'])) {
            $analysis->setEntities(
                array_map(
                    fn($ent) => $ent['name'] ?? 'Unbenannt',
                    $decoded['entities']
                )
            );
        }
        if (isset($decoded['actions']) && is_array($decoded['actions'])) {
            $analysis->setActions(
                array_map(
                    fn($act) => $act['name'] ?? 'Unbenannt',
                    $decoded['actions']
                )
            );
        }
        if (isset($decoded['complexity'])
            && in_array(
                $decoded['complexity'], ['low', 'medium', 'high']
            )
        ) {
            $analysis->setComplexity($decoded['complexity']);
        } else {
            $analysis->setComplexity('unknown');
        }
        if (isset($decoded['architecture'])
            && is_string(
                $decoded['architecture']
            )
        ) {
            $analysis->setArchitecture($decoded['architecture']);
        } else {
            $analysis->setArchitecture('unknown');
        }
        return $analysis;
    }

    public function createImplementationPlan(UserStoryAnalysis $analysis): array
    {
        $requirements = json_encode(
            $analysis->getRequirements(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
        $entities = json_encode(
            $analysis->getEntities(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
        $actions = json_encode(
            $analysis->getActions(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );

        $prompt = <<<PROMPT
Du bist ein Software-Architekt.
Basierend auf den Requirements plane bitte die nötigen Dateien und deren Zweck.


Requirements: {$requirements}
Entities: {$entities}
Actions: {$actions}
Komplexität: {$analysis->getComplexity()}
Architektur: {$analysis->getArchitecture()}

Antworte im JSON-Format:
{
  "files": [
    {"name": "Patient.php", "purpose": "Entity für Patientendaten"},
    {"name": "DashboardController.php", "purpose": "Controller für Dashboard"},
    {"name": "AuthService.php", "purpose": "Login & Auth"}
  ]
}

Important rules:
- Use OpenAPI objects for entities and actions.
- Always include concrete field names and data types for all relevant entities.
- Use basic data models and with relevant properties and refences that are 
needed to cover the user story.
- Only create REST APIs, no templates, no boilerplate code or html rendering.
- Gib das Ergebnis als gültigen JSON-String zurück. Verwende keine escaped 
Slashes, sondern gebe Pfade und Namespaces mit einfachen / bzw. \ aus (wie bei json_encode(..., JSON_UNESCAPED_SLASHES) in PHP). 
PROMPT;

        $response = $this->provider->generateStreamResult($prompt, [
            'model'       => $this->planningModel,
            'temperature' => 0.1,
        ]);

        $this->logger->debug("[Plan]: " . $response);

        $data = LlmResponseParser::parseJson($response);

        if (!isset($data['files']) || !is_array($data['files'])) {
            $data['files'] = [];
        }
        return [
            'files' => array_map(
                fn($f) => $f['name'] ?? 'Unknown.php',
                $data['files']
            ),
        ];
    }

    public function createImplementationPlanFromCoderFeedback(
        string $coderResponse
    ): array {
        $prompt = <<<PROMPT
Der Coder hat diese Antwort gegeben und benötigt mehr Details, um fortzufahren:

\"\"\"
{$coderResponse}
\"\"\"

Analysiere genau, welche Informationen im Coder-Response fehlen oder angefragt werden. Beantworte diese Lücken mit maximal möglicher Konkretion.

Liefere als gültiges JSON:
{
  "requirements": [
    { "name": "string", "beschreibung": "string" }
  ],
  # OpenAPI-Objekte für Entities, z.B.:
  "entities": [
    { "name": "string", "beschreibung": "string", "properties": { "propertyName": { "type": "string", "description": "string" } } }
  ], 
  # OpenAPI-Objekte für Actions, z.B.:
  "actions": [
    { "name": "string", "beschreibung": "string", "method": 
    "GET|POST|PUT|DELETE", "path": "/api/example", "parameters": { "paramName": { "type": "string", "description": "string" } }, "responses": { "200": { "description": "string", "content": { "application/json": { "schema": { "\$ref": "#/components/schemas/ExampleResponse" } } } } } }
  ],
  "files": [
    { "name": "string", "purpose": "string" }
  ],
  "complexity": "low|medium|high",
  "architecture": "a comma seperated list of architectural styles, e.g. microservices, monolith, serverless, ddd, hexagonal, layered, technical, event-driven, cqrs, clean, modular, etc.",
}

Wichtige Regeln:
- Verwende OpenAPI-Objekte für Entities und Actions.
- Ergänze immer konkrete Feldnamen und Datentypen für alle relevanten Entities.
- Wenn Methoden im Code fehlen, definiere sie mit Name und Zweck.
- Falls der Coder unklare Dinge anspricht, triff sinnvolle Annahmen und liefere Defaults.
- Antworte nur mit JSON, keine Erklärungen oder Kommentare.
- Use OpenAPI objects for entities and actions.
- Always include concrete field names and data types for all relevant entities.
- Use basic data models and with relevant properties and refences that are 
needed to cover the user story.
- Only create files that are necessary to implement the user story.
- Only create REST APIs, no templates, no boilerplate code or html rendering.
- Gib das Ergebnis als gültigen JSON-String zurück. Verwende keine escaped 
Slashes, sondern gebe Pfade und Namespaces mit einfachen / bzw. \ aus (wie bei json_encode(..., JSON_UNESCAPED_SLASHES) in PHP).
PROMPT;

        $response = $this->provider->generateStreamResult($prompt, [
            'model'       => $this->planningModel,
            'temperature' => 0.1,
        ]);
        $this->logger->info("[Architekt-Feedback]: " . $response);
        return LlmResponseParser::parseJson($response);
    }
}
