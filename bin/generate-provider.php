<?php

declare(strict_types=1);

use Bono\Agent\Orchestrator;
use Bono\Api\LlmProviderInterface;

require_once __DIR__ . '/../bootstrap.php';

$app = require __DIR__ . '/../config/app.php';
$app->setLogger((new Bono\Factory\LoggerFactory(Orchestrator::class))());

$interface = json_encode(
    LlmProviderInterface::class,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
);

try {
    $reflection = new ReflectionClass(LlmProviderInterface::class);
    $methods = json_encode(
        array_map(
            static fn(ReflectionMethod $method) => [
                'name'       => $method->getName(),
                'parameters' => array_map(
                    static fn(ReflectionParameter $param) => [
                        'name'       => $param->getName(),
                        'type'       => (string)$param->getType(),
                        'isOptional' => $param->isOptional(),
                    ],
                    $method->getParameters()
                ),
                'returnType' => (string)$method->getReturnType(),
            ],
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC)
        ),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );
} catch (ReflectionException $e) {
    echo "Error reflecting interface {$interface}: " . $e->getMessage()
        . PHP_EOL;
    exit(1);
}

$app->processTask(
    <<<USER_STORY
As a developer for this Agent Orchestration Software, 
I need a Gemini provider that implements:

Interface:
{$interface}

Methods: 
{$methods}

Acceptance criteria: 
- Only the provider class should be generated, nothing else.

Non-acceptance criteria:
- No apis should be generated.

USER_STORY
);

