# Architektur

Das Projekt folgt einer modularen Clean Architecture mit CQRS und Event-Driven Ansätzen.

## Verzeichnisstruktur

- `src/` – Hauptquellcode (Agenten, Provider, Cache, Parser, Tools)
- `test/` – Unit\- und Integrationstests
- `generated/` – Generierter Beispielcode
- `config/` – Konfigurationsdateien
- `docs/` – Dokumentation

## Hauptkomponenten

- **Agenten:** Architekt, Coder, Orchestrator
- **Provider:** Verschiedene LLM-Provider (z\.B\. Ollama)
- **Cache:** File\-Cache, Array\-Cache, Decorator
- **Parser & Tools:** Unterstützung für Analyse und Code-Generierung