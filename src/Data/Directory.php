<?php

declare(strict_types=1);

namespace Bono\Data;

use function count;
use function scandir;

class Directory extends File
{
    /** @var array<int, File> */
    private array $files = [];

    public function __construct(
        string $name,
        string $path,
    ) {
        parent::__construct(
            $name,
            0,
            $path,
            'inode/directory'
        );
    }

    /**
     * @return array<int, File>
     */
    public function getFiles(): array
    {
        if (count($this->files) === 0) {
            foreach (scandir($this->getPath()) as $fileName) {
                $size          = 0;
                $path          = '';
                $mime          = '';
                $this->files[] = new File(
                    $fileName,
                    $size,
                    $path,
                    $mime
                );
            }
        }
        return $this->files;
    }
}
