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

/**
 * Class LlmResponseParser
 * This class provides methods to parse responses from LLMs, specifically for
 * extracting code blocks and JSON data.
 */
class LlmResponseParser
{
    static function containsCode(string $response): bool
    {
        return (bool)preg_match('/^```(.*)```$/s', trim($response));
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

    public static function parseJson(string $response): array
    {
        $raw = trim($response);

        // Entferne ```json und ``` falls vorhanden
        $raw = preg_replace('/^```json|^```|```$/m', '', $raw);

        // Schneide nach letztem }
        $jsonEnd = strrpos($raw, '}');

        if ($jsonEnd !== false) {
            $json = substr($raw, 0, $jsonEnd + 1);
        } else {
            $json = $raw;
        }

        // Entferne Steuerzeichen
        $json = preg_replace('/[\x00-\x1F\x7F]/u', '', $json);

        // Escape einzelne Backslashes, die nicht schon doppelt sind
        $json = preg_replace(
            '/(?<!\\\\)\\\\(?![\\\\"\/bfnrtu])/', '\\\\', $json
        );

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log(
                "[JSON-ERROR]: JSON=" . $json .
                ' Error=' . json_last_error_msg()
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
        $end = $start;
        $len = strlen($text);
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
