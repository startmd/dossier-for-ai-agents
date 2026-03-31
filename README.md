# Project Dossier & Audit 📂🤖
### The "Agent-First" Intelligence Toolkit for PHP Projects

**Stop letting your AI guess your codebase. Give it a Mission Map.**

When an AI Coding Assistant (like Gemini, Claude, or GitHub Copilot) drops into a legacy PHP project, it starts playing hide-and-seek. It runs `find` commands, searches for routing files, and attempts to guess if your architecture uses Active Record or Doctrine. This "Discovery Phase" burns massive amounts of the AI's context window (the "Token Tax") and frequently leads to hallucinated paths and generic code that breaks your internal conventions.

**Project Dossier** is a specialized, zero-dependency toolkit designed to scan your codebase and generate high-signal, machine-readable intelligence. It transforms a messy directory tree into a precise Mission Map that an AI Agent can ingest in seconds.

---

## 🧠 The Solution

Instead of standard, human-readable documentation, this toolkit generates explicitly for LLM consumption:

- **Machine-Readable Metadata:** Instant JSON headers so the agent understands scale and scope without thinking.
- **The Trace Table:** Explicit `Route -> Controller -> View` functional lineage. No more guessing how features wire together.
- **House Rules:** Automated extraction of architectural constraints (e.g., "Must extend BaseController").
- **Agent Risk Heatmap:** Guides the AI away from highly complex, fragile files.

---

## 🚀 Quick Start

### Requirements
- PHP 8.2+
- Composer

### 1. Installation
Clone the repository into your project's tool directory:
```bash
git clone https://github.com/your-repo/project-dossier.git dossier
cd dossier && composer install
```

### 2. Generate the Files
Run the two generator commands against your target PHP project path:

**Generate the Architecture Map:**
```bash
php src/bin/app.php generate:dossier /path/to/your/project -o PROJECT_DOSSIER.md
```

**Generate the Health & Risk Report:**
```bash
php src/bin/app.php generate:audit /path/to/your/project -o PROJECT_AUDIT.md
```

### 3. Generate via the Web GUI
If you prefer not to use the terminal, you can generate reports via the beautifully styled, standalone Web GUI.
1. Serve the Project Dossier directory via your local web environment (e.g., `http://localhost/dossier`).
2. Visit `index.php` in your browser.
3. Enter the absolute path to your target PHP project and click Generate! The scripts will execute and stream their output directly to a glassmorphic terminal in the UI.

### 4. How to use with your AI
1. Open your AI coding client of choice (Cursor, Gemini CLI, Claude Desktop).
2. Attach both `PROJECT_DOSSIER.md` and `PROJECT_AUDIT.md` to your prompt.
3. Use a prompt like: *"I have attached the Project Dossier mission maps. Following the documented House Rules and architectural trace table, please build a new Endpoint for resetting passwords."*
4. Watch the AI build perfectly-suited code without running a single blind `ls` or `grep` command.

---

## ⚙️ Advanced Configuration & Deep Dive

If your application uses custom folder structures or bespoke routing logic, Project Dossier is highly configurable via a `dossier.config.php` file. 

For complete instructions on configuring custom paths, interpreting the Trace Tables, and deep-diving into what exactly the AI is analyzing, please see the full **[DOCUMENTATION.md](./DOCUMENTATION.md)**.

---

## 🛠️ Zero-Dependency Fallback System

Project Dossier is completely decoupled from the system it analyzes. It ships with its own robust internal libraries located safely in its own `vendor/` directory, meaning it won't conflict with your target project's dependencies:
- **PHPStan:** For static analysis.
- **PHPLOC:** For complexity metrics.
- **PHP-Parser:** For deep architectural mapping.

---

## 🗺️ Roadmap
- [ ] **Laravel/Symfony Auto-Presets:** Zero-config support for major frameworks.
- [ ] **Database Schema Ingestion:** Add table structures to the Dossier models map.
- [ ] **Graph Visualization:** Export Mermaid.js code for architectural and relationship diagrams.

## 🤝 Contributing
Contributions are welcome! If you find a pattern that isn't being detected or want to add support for a new framework, please open a PR.

---
**Maintained by the Gemini CLI Community.**
