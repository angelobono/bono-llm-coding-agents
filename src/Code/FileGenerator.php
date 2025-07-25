<?php

declare(strict_types=1);

namespace Bono\Code;

use Co\Channel;
use Swoole\Coroutine;
use Bono\Model\CodingTask;
use Bono\Agent\CoderAgent;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Bono\Parser\LlmResponseParser;

/**
 * FileGenerator is responsible for generating files based on a given plan
 * and a CoderAgent. It handles the parallel generation of files using
 * Swoole coroutines and channels.
 */
class FileGenerator implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function generateFilesParallel(
        array $plan,
        CodingTask $result,
        CoderAgent $coder
    ): array {
        $generatedFiles = [];
        Coroutine\run(
            function () use ($plan, &$generatedFiles, $result, $coder) {
                $channels = [];
                foreach ($plan['files'] as $fileName) {
                    $channels[$fileName] = new Channel(1);
                    Coroutine::create(function () use (
                        $result,
                        $fileName,
                        $plan,
                        $channels,
                        $coder
                    ) {
                        $this->processFileGenerationTask(
                            $result,
                            $fileName,
                            $channels[$fileName],
                            $coder
                        );
                    });
                }
                foreach ($channels as $fileName => $channel) {
                    $generatedFiles[$fileName] = $channel->pop(0.5);
                }
            }
        );
        return $generatedFiles;
    }

    private function processFileGenerationTask(
        CodingTask $result,
        string $fileName,
        Channel $channel,
        CoderAgent $coder
    ): void {
        $toolResult = null;
        $maxRounds = 10;
        $round = 0;
        while ($round++ < $maxRounds) {
            if ($toolResult !== null) {
                $coder->injectToolResult($toolResult);
                $toolResult = null;
            }
            $response = trim($coder->generateCode($fileName));

            if (!LlmResponseParser::containsCode($response)) {
                break;
            }
            $savedPath = $this->saveGeneratedFile(
                $result->getId(), $fileName,
                LlmResponseParser::parsePhp($response)
            );
            $channel->push($savedPath);
            break;
        }
    }

    private function saveGeneratedFile(
        string $path,
        string $fileName,
        string $content
    ): string {
        $targetDir = __DIR__ . '/../../generated/' . $path . '/src';

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $fullPath = $targetDir . '/' . basename($fileName);
        file_put_contents($fullPath, $content);

        if (str_ends_with($fileName, '.php')) {
            $lintResult = shell_exec("php -l " . escapeshellarg($fullPath));
            $this->logger->debug("[Lint] " . trim($lintResult));
        }
        return $fullPath;
    }
}
