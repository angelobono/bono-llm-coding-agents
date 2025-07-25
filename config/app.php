<?php

use Bono\Provider\OllamaProvider;
use Bono\Factory\CoderAgentFactory;
use Bono\Factory\OrchestratorFactory;
use Bono\Factory\ArchitectAgentFactory;

$config = require __DIR__ . '/config.php';

$provider = new OllamaProvider($config['OllamaProvider']['url']);

$appFactory = new OrchestratorFactory(
    new ArchitectAgentFactory($provider),
    new CoderAgentFactory($provider)
);

return $appFactory->__invoke();
