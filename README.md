# LLM Coding Agents and Orchestration for PHP
  
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
- Generates generic type annotations for PHP 8\.1\+

## Quick example

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
```

Generated files:

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