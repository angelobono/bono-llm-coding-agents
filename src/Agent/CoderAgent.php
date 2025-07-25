<?php

declare(strict_types=1);

namespace Bono\Agent;

use Bono\Factory\LoggerFactory;
use Bono\Provider\LlmProviderInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

use function trim;

class CoderAgent implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ?string $lastToolResult = null;

    public function __construct(
        private LlmProviderInterface $provider,
        private string $codingModel = 'deepseek-coder:6.7b'
    ) {
        if (! $this->logger) {
            $this->logger = (new LoggerFactory(self::class))->__invoke();
        }
    }

    public function generateCode(
        string $prompt,
        bool $disableTools = false
    ): string {
        if ($this->lastToolResult) {
            $prompt              .= "\n\nZusätzliche Info: " . $this->lastToolResult;
            $this->lastToolResult = null;
        }
        $tools = ! $disableTools
            ? 'WEITERE INFORMATIONEN:
- Wenn du externe Assets brauchst (z.B. Bilder), antworte mit JSON:
  {"tool": "stable_diffusion", "param": "Beschreibung des Bildes"}'
            : '';

        $finalPrompt = <<<PROMPT
{$prompt}

{$tools}
  
PROMPT;

        $this->logger->info("[Coder] sendet Nachricht an Modell", [
            'model'  => $this->codingModel,
            'prompt' => $finalPrompt,
        ]);

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
