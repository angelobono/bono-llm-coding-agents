<?php

declare(strict_types=1);

namespace Bono\Tests\Mock;

use function json_decode;
use function stream_context_get_options;

class HttpStreamMock
{
    public static string $nextResponse = '';
    public static array $lastPayload   = [];
    private int $readCount             = 0;
    public $context; // <- Sichtbarkeit geändert

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        if ($this->context) {
            $opts = stream_context_get_options($this->context);
            if (isset($opts['http']['content'])) {
                self::$lastPayload = json_decode($opts['http']['content'], true);
            }
        }
        return true;
    }

    public function stream_read($count)
    {
        if ($this->readCount === 0) {
            $this->readCount++;
            return self::$nextResponse;
        }
        return '';
    }

    public function stream_eof()
    {
        return $this->readCount > 0;
    }

    public function stream_stat()
    {
        return [];
    }

    public function stream_set_option($option, $arg1, $arg2)
    {
        return true;
    }

    public function stream_context_create($options)
    {
        return true;
    }
}
