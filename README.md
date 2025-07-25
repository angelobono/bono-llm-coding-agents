# Bono LLM Coding Agents and Orchestration for PHP

**PHP LLM Coding Agents**  
Automated multi-agent architecture for code generation, analysis, and orchestration with could support various AI providers.
This project is actually in a experimental state, but already usable.

Find more details in the [HTML-documentation](https://angelobono.github.io/bono-llm-coding-agents/).

## Features

- Multi-agent architecture (Architect, Coder, Orchestrator)
- Support for various LLM providers (e\.g\. Ollama)
- Caching with File\-Cache, Array\-Cache, and Decorator
- Modular design
- Integration and unit tests
- Compatible with PHP 8\.2\+

## Installation

Requirements:

- PHP >= 8\.2
- Composer
- Optional: Ollama

**Step 1:** Clone the repository
```bash
git clone https://github.com/angelobono/bono-llm-coding-agents.git
cd bono-llm
```

**Step 2:** Install Ollama (see Ollama website or use a docker image)

**Step 3:** Download models, the default setup uses:
```bash
ollama pull llama3.2:3b
ollama pull deepseek-coder:6.7b:
```

**Step 3:** Install dependencies
```bash
composer install
```

## Directory Structure

- `src/` – Main source code (Agents, Provider, Cache, Parser, Tools)
- `test/` – Unit and integration tests
- `generated/` – Generated example code
- `config/` – Configuration files
- `docs/` – Documentation

## Usage

Example using the Ollama LLM provider:

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

## Tests

Run unit and integration tests:

```bash
composer test
```

Test coverage:

```bash
composer test-coverage
```

## Known Issues

- Ollama sometimes returns empty responses, which can lead to errors in the agent workflow. The workflow will retry automatically, but this can cause longer execution times.

## Contributing

Contributions are welcome!  
Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/your-feature`)
3. Commit your changes (`git commit -am 'Added feature'`)
4. Push to your fork (`git push origin feature/your-feature`)
5. Create a pull request

Please follow the [PSR\-12 Coding Standard](https://www.php-fig.org/psr/psr-12/) and run static analysis before your PR:

```bash
composer analyse
composer cs-check
```

## License

This project is licensed under the [BSD-3-Clause License](LICENSE.md).

## Contact

Maintainer: [angelobono](https://github.com/angelobono)  
Questions and feedback welcome via GitHub Issues or Pull Request.

---

**Have fun developing with Bono LLM Coding Agents!**