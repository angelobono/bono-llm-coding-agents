# Examples

## Example using the Ollama LLM provider:

```php
use Bono\Factory\ArchitectAgentFactory;
use Bono\Factory\CoderAgentFactory;
use Bono\Orchestrator;
use Bono\Provider\OllamaProvider;
use Bono\Tests\Mock\StableDiffusionMock;

use function array_keys;
use function implode;

// 1) REAL provider → uses your local Ollama
$ollama = new OllamaProvider('http://localhost:11434/api');

// 2) Agents from factory
$architect = (new ArchitectAgentFactory($ollama))->__invoke();
$coder     = (new CoderAgentFactory($ollama))->__invoke();

// 3) Build orchestrator
$orchestrator = new Orchestrator($architect, $coder);

// 4) Register StableDiffusion MOCK (no real API call)
$orchestrator->registerTool('stable_diffusion', new StableDiffusionMock());

// 5) Test user story
$userStory = <<<TXT
As a doctor, I want a dashboard with patient records.
As a developer, I want us to use only REST APIs.
The dashboard should show an overview of all patients, including name, age, and diagnosis.
There should also be a search function to find patients by name.
TXT;

// 6) Execute task
$result = $orchestrator->processTask($userStory);

// Optional → Output logging
echo "\nAnalysis complexity: " . $result->analysis->complexity;
echo "\nGenerated files: " . implode(', ', array_keys($result->files));
```
