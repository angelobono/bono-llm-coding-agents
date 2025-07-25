<?php

declare(strict_types=1);

namespace Bono\Api;

interface Tool
{
    public function execute(string $param): string;
}
