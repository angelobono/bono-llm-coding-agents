# Examples

## Example using the Ollama LLM provider:

```php
<?php

declare(strict_types=1);

use Bono\Factory\ArchitectAgentFactory;
use Bono\Factory\CoderAgentFactory;
use Bono\Orchestrator;
use Bono\Provider\OllamaProvider;

use function array_keys;
use function implode;

// 1) REAL provider → uses your local Ollama
$ollama = new OllamaProvider('http://localhost:11434/api');

// 2) Agents from factory
$architect = (new ArchitectAgentFactory($ollama))->__invoke();
$coder     = (new CoderAgentFactory($ollama))->__invoke();

// 3) Build orchestrator
$orchestrator = new Orchestrator($architect, $coder);

// 4) Register StableDiffusion (optional)
// $orchestrator->registerTool('stable_diffusion', new StableDiffusion());

// 5) Test user story
$userStory = 'As a doctor, I want a dashboard with patient records.';

// 6) Execute task
$result = $orchestrator->processTask($userStory);

// Optional → Output logging
echo '\nAnalysis complexity: ' . $result->analysis->complexity;
echo '\nGenerated files: ' . implode(', ', array_keys($result->files));
```
