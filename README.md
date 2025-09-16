# LLM Coding Agents and Orchestration for PHP

Generative AI with automated multi-agent architecture for PHP code generation,
analysis, and agent orchestration. It can support various AI providers with custom
implementation, the default is using an OllamaProvider, so it can be used
completely local.

This project is in an experimental state, but already usable.

It is a simple implementation but customizable, extandable and powerful tool to
generate PHP code from user stories.

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
- Parallel task processing with `swoole` / `openswoole` extension
- Retry mechanisms
- Supports simple user stories
- Supports user stories with acceptance criteria and non-acceptance criteria

## Installation

You need one or more LLM Providers to run this project, Ollama is default.
You can find more information about Ollama [here](https://ollama.com/)
or in the [HTML-documentation](https://angelobono.github.io/bono-llm-coding-agents/).

## Quick examples

Note: The default prompts are configured to generate REST-APIs.

### Simple user story

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$app = require 'config/app.php';
$app->processTask('As a doctor, I want a dashboard with patient records.');
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

### Detailed user story

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$app = require 'config/app.php';
$app->processTask(<<<USER_STORY
As a doctor, I want a dashboard with patient records.
            
Acceptance criteria
- The API provides an endpoint to list patient records with key information (GET /api/patients returns name, ID, diagnosis).
- The API supports searching and filtering patient records by name or ID via query parameters (GET /api/patients?name=...&id=...).
- The API provides an endpoint to retrieve detailed information for a single patient (GET /api/patients/{id}).
- Access to all patient endpoints requires authentication (e.g., JWT token).
- The API responses are structured in JSON and support clients on desktop and tablet.

Non-acceptance criteria
- The API does not provide endpoints to edit or delete patient records (PUT, DELETE are not available).
- The API does not provide endpoints for analytics or statistics.
- The API does not provide endpoints to export patient data (e.g., no CSV/PDF export).
- The API does not send notifications for new records.
- The API does not connect or synchronize with external hospital systems.
USER_STORY);
```

## Known Issues

- Ollama sometimes returns empty or invalid json responses, which can lead to errors in the agent workflow. The
  workflow will retry automatically, but this can cause longer execution times.

## Contributing

Contributions are welcome!  
Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/your-feature`)
3. Commit your changes (`git commit -am 'Added feature'`)
4. Push to your fork (`git push origin feature/your-feature`)
5. Create a pull request

Please follow the [PSR\-12 Coding Standard](https://www.php-fig.org/psr/psr-12/) and run static
analysis before your PR:

```bash
composer analyse
composer cs-check
```

## License

This project is licensed under the [BSD-3-Clause License](LICENSE.md).

## Thanks

Special thanks to the following:

### Libraries / Tools

I used in this project:

- [laminas/*](https://laminas.dev/)
- [mezzio/*](https://mezzio.dev/)
- [symfony/*](https://symfony.com/)
- [swoole/ide-helper](https://github.com/swoole/ide-helper)
- [ollama](https://ollama.com/)
- [phpstan](https://phpstan.org/)
- [psr/*](https://www.php-fig.org/)
- [vimeo/psalm](https://github.com/vimeo/psalm)
- [phpunit/phpunit](https://github.com/phpunit/phpunit)
- [ramsey/uuid](https://github.com/ramsey/uuid)

### People

I recommend to follow their videos, books, and articles:

- [Marco Pivetta (Ocramius)](https://github.com/ocramius)
- [Ralf Eggert](https://github.com/ralfeggert)
- [Kent Beck](https://github.com/kentbeck)
- [Martin Fowler](https://github.com/martinfowler)
- [Gregor Hohpe](https://github.com/elit0451/EIPatterns)
- [Robert C. Martin (Uncle Bob)](https://github.com/unclebob)
- [Dr. Jeff Sutherland](https://github.com/scrumatscale/official-guide)

## Contact

Maintainer: [angelobono](https://github.com/angelobono)  
Questions and feedback welcome via GitHub Issues or Pull Request.

---

**Have fun developing with Bono LLM Coding Agents!**
