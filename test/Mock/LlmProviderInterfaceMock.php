<?php

declare(strict_types=1);

namespace Bono\Tests\Mock;

use Bono\Api\LlmProviderInterface;

use function count;

class LlmProviderInterfaceMock implements LlmProviderInterface
{
    private array $script;
    private int $index = 0;

    public function __construct(array $script)
    {
        $this->script = $script;
    }

    public function generate(
        string $prompt,
        array $options = []
    ): string {
        if ($this->index < count($this->script)) {
            return $this->script[$this->index++];
        }
        return "Keine weitere Dummy-Antwort.";
    }

    public function generateStreamResult(string $prompt, array $options = []
    ): string {
        if ($this->index < count($this->script)) {
            return $this->script[$this->index++];
        }
        return "Keine weitere Dummy-Antwort.";
    }

    public function generateStream(
        string $prompt,
        callable $onData,
        array $options = []
    ): void {
        if ($this->index < count($this->script)) {
            $onData($this->script[$this->index++]);
        } else {
            $onData("Keine weitere Dummy-Antwort.");
        }
    }
}
