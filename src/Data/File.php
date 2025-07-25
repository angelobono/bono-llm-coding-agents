<?php

declare(strict_types=1);

namespace Bono\Data;

class File
{
    private string $name;
    private int $size;
    private string $path;
    private string $mime;

    public function __construct(
        string $name,
        int $size,
        string $path,
        string $mime
    ) {
        $this->name = $name;
        $this->size = $size;
        $this->path = $path;
        $this->mime = $mime;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMime(): string
    {
        return $this->mime;
    }
}
