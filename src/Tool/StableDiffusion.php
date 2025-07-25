<?php

declare(strict_types=1);

namespace Bono\Tool;

use Bono\Api\Tool;

use function time;
use function json_encode;
use function json_decode;
use function base64_decode;
use function file_put_contents;
use function file_get_contents;
use function stream_context_create;

class StableDiffusion implements Tool
{
    private string $baseUrl;

    public function __construct(
        string $baseUrl = 'http://localhost:7860/sdapi/v1/txt2img'
    ) {
        $this->baseUrl = $baseUrl;
    }

    public function execute(string $param): string
    {
        $payload = [
            'prompt' => $param,
            'steps'  => 20,
            'width'  => 512,
            'height' => 512,
        ];

        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json",
                'content' => json_encode($payload),
            ],
        ]);
        $result = file_get_contents($this->baseUrl, false, $context);
        $json = json_decode($result, true);

        if (!empty($json['images'][0])) {
            $imageData = $json['images'][0];
            $file = 'output_' . time() . '.png';
            file_put_contents($file, base64_decode($imageData));
            return "Bild generiert: $file";
        }
        return "Fehler bei der Bildgenerierung";
    }
}
