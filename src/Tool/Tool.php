<?php

declare(strict_types=1);

namespace Bono\Tool;

interface Tool
{
    public function execute(string $param): string;
}
