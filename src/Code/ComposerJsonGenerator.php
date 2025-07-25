<?php

declare(strict_types=1);

namespace Bono\Code;

use Bono\Model\CodingTask;
use Bono\Agent\CoderAgent;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Bono\Parser\LlmResponseParser;

/**
 * ComposerJsonGenerator is responsible for generating a composer.json file
 * based on the analysis of PHP files provided in the TaskResult.
 * It extracts use statements from the files and generates a composer.json
 * using a CoderAgent to interact with an LLM.
 */
class ComposerJsonGenerator implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function generate(CodingTask $result, CoderAgent $coder): void
    {
        $targetDir = __DIR__ . '/../../generated/' . $result->getId() . '/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $useStatements = $this->getUseStatementsFromAnalysis($result->files);
        $prompt
            = "Erstelle eine composer.json mit diesen use-Statements: {$useStatements}";
        $response = $coder->generateCode($prompt, true);
        $result->files['composer.json'] = $targetDir . 'composer.json';
        file_put_contents(
            $targetDir . 'composer.json',
            LlmResponseParser::parseJson($response)
        );
    }

    private function getUseStatementsFromAnalysis(array $files): string
    {
        $useStatements = [];
        foreach ($files as $filePath) {
            if (is_file($filePath)) {
                $content = file_get_contents($filePath);
                if (preg_match_all(
                    '/use\s+([a-zA-Z0-9_\\\\]+);/', $content, $matches
                )
                ) {
                    foreach ($matches[1] as $use) {
                        $useStatements[] = $use;
                    }
                }
            }
        }
        return implode(', ', array_unique($useStatements));
    }
}
