<?php

declare(strict_types=1);

namespace Bono\Provider;

use Psr\Log\LoggerAwareTrait;
use Bono\Factory\LoggerFactory;
use Psr\Log\LoggerAwareInterface;
use Bono\Api\LlmProviderInterface;

use function fgets;
use function fopen;
use function rtrim;
use function fclose;
use function strlen;
use function array_merge;
use function json_decode;
use function json_encode;
use function file_get_contents;
use function stream_context_create;

class OllamaProvider implements LlmProviderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private string $baseUrl;
    private array $defaultOptions;

    public function __construct(
        string $baseUrl = 'http://localhost:11434/api',
        array $defaultOptions
        = [
            'temperature' => 0.3,
            'top_p'       => 0.9,
            'num_ctx'     => 16000,
        ]
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->defaultOptions = $defaultOptions;

        // Falls kein Logger gesetzt wurde → NullLogger
        if (!$this->logger) {
            $this->logger = (new LoggerFactory(self::class))->__invoke();
        }
    }

    public function generate(string $prompt, array $options = []): string
    {
        $model = $options['model'] ?? 'llama3.2:3b';

        $this->logger->info("OllamaProvider → Sende Prompt an Modell", [
            'model'      => $model,
            'prompt_len' => strlen($prompt),
        ]);

        $payload = [
            'model'   => $model,
            'prompt'  => $prompt,
            'stream'  => false,
            'options' => array_merge(
                $this->defaultOptions, $options['options'] ?? []
            ),
        ];

        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json",
                'content' => json_encode($payload),
            ],
        ]);

        $result = @file_get_contents(
            $this->baseUrl . '/generate', false, $context
        );

        if ($result === false) {
            $this->logger->error("OllamaProvider → Anfrage fehlgeschlagen", [
                'url' => $this->baseUrl . '/generate',
            ]);
            return '';
        }

        $json = json_decode($result, true);
        $response = $json['response'] ?? '';

        $this->logger->debug("OllamaProvider → Antwort empfangen", [
            'response_len' => strlen($response),
        ]);

        return $response;
    }

    public function generateStreamResult(
        string $prompt,
        array $options = []
    ): string {
        $result = '';
        $this->generateStream($prompt, function ($chunk) use (&$result) {
            $result .= $chunk;
        }, $options);
        return $result;
    }

    // src/Provider/OllamaProvider.php
    public function generateStream(string $prompt, callable $onData,
        array $options = []
    ): void {
        $model = $options['model'] ?? 'llama3.1:7b';

        $this->logger->debug("OllamaProvider → Sende Stream-Prompt an Modell", [
            'model'      => $model,
            'prompt_len' => strlen($prompt),
        ]);

        $payload = [
            'model'   => $model,
            'prompt'  => $prompt,
            'stream'  => true,
            'options' => array_merge(
                $this->defaultOptions, $options['options'] ?? []
            ),
        ];

        $url = $this->baseUrl . '/generate';
        $opts = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json",
                'content' => json_encode($payload),
                'timeout' => 60,
            ],
        ];
        $context = stream_context_create($opts);

        $handle = @fopen($url, 'r', false, $context);
        if (!$handle) {
            $this->logger->error(
                "OllamaProvider → Stream-Anfrage fehlgeschlagen",
                ['url' => $url]
            );
            return;
        }

        while (($line = fgets($handle)) !== false) {
            $json = json_decode($line, true);
            if (isset($json['response'])) {
                $onData($json['response']);
            }
        }
        fclose($handle);
    }
}
