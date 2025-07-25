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