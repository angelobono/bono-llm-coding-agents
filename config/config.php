<?php

use Bono\Config\Env;
use Monolog\Level;
use Bono\Agent\CoderAgent;
use Psr\Log\LoggerInterface;
use Bono\Agent\ArchitectAgent;
use Bono\Provider\OllamaProvider;

Env::initialize();

return [
    LoggerInterface::class => [
        getenv('LOG_PATH') ?: __DIR__ . '/../logs/app.log',
        getenv('LOG_LEVEL') ?: Level::Info,
    ],
    OllamaProvider::class  => [
        getenv('OLLAMA_URL') ?: 'http://localhost:11434/api',
    ],
    ArchitectAgent::class  => [
        OllamaProvider::class,
        getenv('ARCHITECT_AGENT_ANALYSIS_MODEL') ?? 'llama3.2:3b',
        getenv('ARCHITECT_AGENT_GENERATION_MODEL') ?? 'llama3.2:3b',
        /*'tools' => [
            'stable_diffusion' => \Bono\Tool\StableDiffusion::class,
        ],*/
    ],
    CoderAgent::class      => [
        OllamaProvider::class,
        getenv('CODER_AGENT_CODING_MODEL') ?? 'qwen2.5-coder:3b',
    ],
];
