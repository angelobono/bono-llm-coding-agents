# Development

## Requirements

- PHP >= 8.2
- Composer
- Optional: Ollama, APCu

## Setup

1. Clone the repository:
    ```bash
    git clone https://github.com/angelobono/bono-llm-coding-agents.git
    cd bono-llm
    ```
2. Install dependencies:
    ```bash
    composer install
    ```
3. (Optional) Install Ollama and download models:
    ```bash
    ollama pull llama3.2:3b
    ollama pull deepseek-coder:6.7b
    ```

## Code Style

- PSR-12 Coding Standard
- Static analysis with Psalm and PHPStan

## Testing

Run all tests:
```bash
composer test
```

### Test coverage:

```bash
composer test-coverage
```

### Static Analysis

```bash
composer analyse
composer psalm
```

### Code-Style check and fix

```bash
composer cs-check
composer cs-fix
```

## Development Guidelines

- Create new classes in the src/ directory, tests in the corresponding test/ path.
- Use src/Provider/ for LLM providers, src/Factory/ for agent factories.