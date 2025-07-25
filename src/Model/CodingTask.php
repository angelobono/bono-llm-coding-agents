<?php

declare(strict_types=1);

namespace Bono\Model;

use function md5;

/**
 * CodingTask represents a coding task that includes the user story,
 * the files generated, and the analysis of the task.
 * It is used to encapsulate the results of a coding task.
 */
class CodingTask
{
    private string $id;
    public bool $success = false;
    public array $files = []; // ['filename' => ['size' => ..., 'path' => ...]]
    public ?UserStoryAnalysis $analysis = null;
    public ?string $validation = null;
    public string $message = '';

    public function __construct(string $userStory)
    {
        $this->id = md5($userStory);
    }

    public function toArray(): array
    {
        return [
            'success'    => $this->success,
            'files'      => $this->files,
            'analysis'   => $this->analysis?->toArray() ?? [],
            'validation' => $this->validation,
            'message'    => $this->message,
        ];
    }

    public function getId(): string
    {
        return $this->id;
    }
}
