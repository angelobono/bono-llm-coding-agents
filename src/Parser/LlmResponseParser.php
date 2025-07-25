<?php

declare(strict_types=1);

namespace Bono\Parser;

use InvalidArgumentException;

use function error_log;
use function json_decode;
use function json_last_error;
use function json_last_error_msg;
use function preg_match;
use function strlen;
use function strpos;
use function strrpos;
use function substr;
use function trim;

use const JSON_ERROR_NONE;

class LlmResponseParser
{
    static function containsCode(string $response): bool
    {
        return (bool) preg_match('/^```(.*)```$/s', trim($response));
    }

    static function parsePhp(string $response): string
    {
        $raw = trim($response);

        if (preg_match('/^```php\s*(.*?)\s*```$/s', $raw, $matches)) {
            return trim($matches[1]);
        }
        if (preg_match('/^```\s*(.*?)\s*```$/s', $raw, $matches)) {
            return trim($matches[1]);
        }
        return $raw;
    }

    static function parseJson(string $response): array
    {
        $raw = trim($response);

        // Suche nach dem ersten JSON-Codeblock
        if (preg_match('/```json\s*(.*?)\s*```/s', $raw, $matches)) {
            $json = trim($matches[1]);
        } elseif (preg_match('/```(.*?)```/s', $raw, $matches)) {
            $json = trim($matches[1]);
        } else {
            // Robust: Finde das erste vollständige JSON-Objekt (auch verschachtelt)
            $json = self::extractFirstJsonObject($raw);
        }

        // Entferne alles nach dem letzten schließenden }
        $jsonEnd = strrpos($json, '}');
        if ($jsonEnd !== false) {
            $json = substr($json, 0, $jsonEnd + 1);
        }

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log(
                "[JSON-ERROR]: JSON=" . $json . ' Error='
                . json_last_error_msg()
            );
            throw new InvalidArgumentException(
                'Invalid JSON format: ' . json_last_error_msg()
            );
        }
        return $data;
    }

    // Hilfsfunktion: Extrahiere das erste vollständige JSON-Objekt aus dem Text
    private static function extractFirstJsonObject(string $text): string
    {
        $start = strpos($text, '{');
        if ($start === false) {
            return $text;
        }
        $depth = 0;
        $end   = $start;
        $len   = strlen($text);
        for ($i = $start; $i < $len; $i++) {
            if ($text[$i] === '{') {
                $depth++;
            }
            if ($text[$i] === '}') {
                $depth--;
            }
            if ($depth === 0 && $i > $start) {
                $end = $i;
                break;
            }
        }
        return substr($text, $start, $end - $start + 1);
    }
}
