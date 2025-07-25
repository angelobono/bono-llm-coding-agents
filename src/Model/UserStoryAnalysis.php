<?php

declare(strict_types=1);

namespace Bono\Model;

use InvalidArgumentException;

use function explode;
use function is_array;
use function is_float;
use function is_int;
use function is_string;

/**
 * Class UserStoryAnalysis
 * This class represents an analysis of a user story, including its
 * requirements, entities, actions, complexity, and architecture.
 */
class UserStoryAnalysis
{
    public string $originalStory;
    public string $architecture;
    private array $requirements = [];
    public array $entities = [];
    public array $actions = [];
    public string $complexity = 'unknown';

    public function __construct(string $story)
    {
        $this->originalStory = $story;
    }

    public function getRequirements(): array
    {
        return $this->requirements;
    }

    public function setRequirements(array $requirements): void
    {
        if (empty($requirements)) {
            throw new InvalidArgumentException('Requirements cannot be empty.');
        }
        $this->requirements = $requirements;
    }

    public function setEntities(mixed $param): void
    {
        if (empty($param)) {
            throw new InvalidArgumentException('Entities cannot be empty.');
        }
        if (is_array($param)) {
            $this->entities = $param;
        } elseif (is_string($param)) {
            $this->entities = explode(',', $param);
        } else {
            throw new InvalidArgumentException(
                'Entities must be an array or a comma-separated string.'
            );
        }
    }

    public function setActions(mixed $param): void
    {
        if (empty($param)) {
            throw new InvalidArgumentException('Actions cannot be empty.');
        }
        if (is_array($param)) {
            $this->actions = $param;
        } elseif (is_string($param)) {
            $this->actions = explode(',', $param);
        } else {
            throw new InvalidArgumentException(
                'Actions must be an array or a comma-separated string.'
            );
        }
    }

    public function setComplexity(mixed $param): void
    {
        if (empty($param)) {
            throw new InvalidArgumentException('Complexity cannot be empty.');
        }
        if (is_string($param)) {
            $this->complexity = $param;
        } elseif (is_int($param) || is_float($param)) {
            $this->complexity = (string)$param;
        } else {
            throw new InvalidArgumentException(
                'Complexity must be a string, integer, or float.'
            );
        }
    }

    public function setArchitecture(string $architecture): void
    {
        if (empty($architecture)) {
            throw new InvalidArgumentException('Architecture cannot be empty.');
        }
        $this->architecture = $architecture;
    }

    public function getEntities(): array
    {
        return $this->entities;
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function getArchitecture(): string
    {
        return $this->architecture;
    }

    public function getComplexity(): string
    {
        return $this->complexity;
    }

    public function getOriginalStory()
    {
        return $this->originalStory;
    }

    public function toArray(): array
    {
        return [
            'originalStory' => $this->getOriginalStory(),
            'requirements'  => $this->getRequirements(),
            'entities'      => $this->getEntities(),
            'actions'       => $this->getActions(),
            'complexity'    => $this->getComplexity(),
            'architecture'  => $this->getArchitecture(),
        ];
    }

}
