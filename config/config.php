<?php

use Bono\Provider\OllamaProvider;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

return [
    'OllamaProvider' => [
        'url' => getenv('OLLAMA_URL') ?: 'http://localhost:11434/api',
    ],
    'ArchitectAgent' => [
        'ollamaProvider' => OllamaProvider::class,
        'analysisModel' => 'llama3.2:3b',
        'generationModel' => 'llama3.2:3b',
        /*'tools' => [
            'stable_diffusion' => \Bono\Tool\StableDiffusion::class,
        ],*/
    ],
    'CoderAgent' => [
        'ollamaProvider' => OllamaProvider::class,
        'codingModel' => 'deepseek-coder:6.7b',
    ],
];
