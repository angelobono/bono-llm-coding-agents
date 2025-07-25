# Bono LLM Coding Agents and Orchestration for PHP

**PHP LLM Coding Agents**  
Automated multi-agent architecture for code generation, analysis, and orchestration.
It can support various AI providers with custom implementation, the default is using an OllamaProvider. 
This project is in an experimental state, but already usable.

Find more details in the [HTML-documentation](https://angelobono.github.io/bono-llm-coding-agents/).

## Features

- Multi-agent architecture (Architect, Coder, Orchestrator)
- Support for various LLM providers (e\.g\. Ollama)
- Caching with File\-Cache, Array\-Cache, and Decorator
- Modular design
- Integration and unit tests
- Compatible with PHP 8\.2\+
- Generated files will be linted

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
ollama pull qwen2.5-coder:3b
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

Generated log:

```php
[2025-07-25T11:45:00.760476+00:00] Bono\Orchestrator.INFO: Tool registriert {"tool":"stable_diffusion"} []
[2025-07-25T11:45:00.761640+00:00] Bono\Orchestrator.INFO: === ANALYSE-PHASE === [] []
[2025-07-25T11:47:10.640634+00:00] Bono\Orchestrator.INFO: === PLANUNGSPHASE === [] []
[2025-07-25T11:48:19.429492+00:00] Bono\Orchestrator.INFO: Planung abgeschlossen {"success":true,"files_count":6} []
[2025-07-25T11:48:19.429952+00:00] Bono\Orchestrator.INFO: === CODE-GENERIERUNG für Datei: Patient.php === [] []
[2025-07-25T11:48:54.634921+00:00] Bono\Orchestrator.INFO: === CODE-GENERIERUNG für Datei: PatientRecord.php === [] []
[2025-07-25T11:49:14.434354+00:00] Bono\Orchestrator.INFO: === CODE-GENERIERUNG für Datei: DashboardController.php === [] []
[2025-07-25T11:49:39.028143+00:00] Bono\Orchestrator.INFO: === CODE-GENERIERUNG für Datei: DashboardView.php === [] []
[2025-07-25T11:50:16.103784+00:00] Bono\Orchestrator.INFO: === CODE-GENERIERUNG für Datei: AuthService.php === [] []
[2025-07-25T11:50:34.001709+00:00] Bono\Orchestrator.INFO: === CODE-GENERIERUNG für Datei: DatabaseConnection.php === [] []
[2025-07-25T11:50:54.951131+00:00] Bono\Orchestrator.INFO: === CODE-GENERIERUNG für Datei: composer.json === [] []
```

Generated file structure:

```php
📂 d7b5e56758d02ddde13307b955372e66/
    📄 composer.json
    🗂️ src/
        📂 App/
            🎮 Controller/
                📄 DashboardController.php
            🗃️ Database/
                📄 DatabaseConnection.php
            📂 Entity/
                📄 Patient.php
                📄 PatientRecord.php
            🔧 Service/
                📄 AuthService.php
            🖼️ View/
                📄 DashboardView.php
```

Generated composer file:

```json
{
    "name": "angelobono/bono-generated",
    "description": "Ein Projekt generiert von angelobono/bono",
    "type": "library",
    "require": {
        "php": "^8.2",
        "ext-json": "*",
        "symfony/uid": "^7.0",
        "doctrine/orm": "^3.0",
        "monolog/monolog": "^3.0",
        "zendframework/zend-diactoros": "^2.5",
        "psr/http-message": "^1.1"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0",
        "phpstan/phpstan": "^1.10",
        "squizlabs/php_codesniffer": "^3.7",
        "vimeo/psalm": "^5.0"
    }
}
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