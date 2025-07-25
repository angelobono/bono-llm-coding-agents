<?php

declare(strict_types=1);

namespace Bono\Agent;

use Bono\Factory\LoggerFactory;
use Bono\Provider\LlmProviderInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

use function trim;

/**
 * CoderAgent is responsible for generating code based on a given prompt.
 * It uses an LLM provider to generate code and can inject tool results
 * for additional context.
 */
final class CoderAgent implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ?string $lastToolResult = null;

    public function __construct(
        private readonly LlmProviderInterface $provider,
        private readonly string $codingModel = 'qwen2.5-coder:3b'
    ) {
        if (!$this->logger) {
            $this->logger = (new LoggerFactory(self::class))->__invoke();
        }
    }

    public function generateCode(
        string $prompt,
        bool $disableTools = false
    ): string {
        if ($this->lastToolResult) {
            $prompt .= "\n\nZusätzliche Info: " . $this->lastToolResult;
            $this->lastToolResult = null;
        }
        $tools = !$disableTools
            ? 'WEITERE INFORMATIONEN:
- Wenn du externe Assets brauchst (z.B. Bilder), antworte mit JSON:
  {"tool": "stable_diffusion", "param": "Beschreibung des Bildes"}'
            : '';

        $finalPrompt = <<<PROMPT
{$prompt}

{$tools}
  
PROMPT;

        $response = $this->provider->generateStreamResult($finalPrompt, [
            'model'       => $this->codingModel,
            'temperature' => 0.0,
        ]);
        return trim($response);
    }

    public function injectToolResult(string $toolResult): void
    {
        $this->lastToolResult = $toolResult;
    }
}
