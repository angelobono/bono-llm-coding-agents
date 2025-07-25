<?php

declare(strict_types=1);

namespace Bono\Data;

use function md5;

class TaskResult
{
    private string $id;
    public bool $success                = false;
    public array $files                 = []; // ['filename' => ['size' => ..., 'path' => ...]]
    public ?UserStoryAnalysis $analysis = null;
    public ?string $validation          = null;
    public string $message              = '';

    public function __construct(string $userStory)
    {
        $this->id = md5($userStory);
    }

    public function toArray(): array
    {
        return [
            'success'    => $this->success,
            'files'      => $this->files,
            'analysis'   => $this->analysis ? [
                'requirements' => $this->analysis->getRequirements(),
                'entities'     => $this->analysis->entities,
                'actions'      => $this->analysis->actions,
                'complexity'   => $this->analysis->complexity,
                'architecture' => $this->analysis->architecture,
            ] : null,
            'validation' => $this->validation,
            'message'    => $this->message,
        ];
    }

    public function getId(): string
    {
        return $this->id;
    }
}
