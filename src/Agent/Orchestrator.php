<?php

declare(strict_types=1);

namespace Bono\Agent;

use Exception;
use Bono\Model\CodingTask;
use Bono\Code\FileGenerator;
use Psr\Log\LoggerAwareTrait;
use Bono\Factory\LoggerFactory;
use Psr\Log\LoggerAwareInterface;
use Bono\Code\ComposerJsonGenerator;

/**
 * Orchestrator is responsible for coordinating the tasks of analyzing a user
 * story, creating an implementation plan, generating files, and creating a
 * composer.json file. It uses ArchitectAgent for analysis and planning,
 * CoderAgent for code generation, and FileGenerator for file management.
 */
class Orchestrator implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ArchitectAgent $architekt;
    private CoderAgent $coder;
    private FileGenerator $fileGenerator;
    private ComposerJsonGenerator $composerJsonGenerator;

    public function __construct(
        ArchitectAgent $architekt,
        CoderAgent $coder,
        ?FileGenerator $fileGenerator = null,
        ?ComposerJsonGenerator $composerJsonGenerator = null
    ) {
        if (!$this->logger) {
            $this->logger = (new LoggerFactory(self::class))();
        }
        $this->coder = $coder;
        $this->architekt = $architekt;
        $this->fileGenerator = $fileGenerator ?? new FileGenerator();
        $this->composerJsonGenerator = $composerJsonGenerator
            ?? new ComposerJsonGenerator();

        $this->fileGenerator->setLogger(
            method_exists($this->logger, 'withName')
                ? $this->logger->withName(FileGenerator::class)
                : $this->logger
        );
        $this->composerJsonGenerator->setLogger(
            method_exists($this->logger, 'withName')
                ? $this->logger->withName(ComposerJsonGenerator::class)
                : $this->logger
        );
    }

    /**
     * @throws Exception
     */
    public function processTask(string $userStory): CodingTask
    {
        $result = new CodingTask($userStory);
        $result->analysis = $this->architekt->analyseUserStory($userStory);
        $plan = $this->architekt->createImplementationPlan($result->analysis);
        $result->files = $this->fileGenerator->generateFilesParallel(
            $plan,
            $result,
            $this->coder
        );
        $this->composerJsonGenerator->generate($result, $this->coder);
        return $result;
    }
}
