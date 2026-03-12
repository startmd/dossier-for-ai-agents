# Project Dossier & Audit 📂🤖
### The "Agent-First" Intelligence Toolkit for PHP Projects

**Stop wasting tokens. Stop Agent confusion. Start shipping.**

Traditional project documentation is written for humans—it's wordy, lacks exact paths, and misses the structural "connective tissue" that AI Agents (like Gemini, Claude, or GPT) need to work effectively. 

**Project Dossier** is a specialized toolkit designed to scan your codebase and generate high-signal, machine-readable intelligence. It transforms a messy directory into a precise "Mission Map" that an AI Agent can ingest in seconds to understand exactly where to go and what rules to follow.

---

## 🧠 The "Agent-First" Philosophy

Most documentation fails AI Agents in three ways:
1. **Token Waste:** Agents spend 50% of their context window just "finding" files and running `ls` or `find` commands.
2. **Hallucination:** Without exact paths and clear relationships, Agents guess folder structures and class locations.
3. **Convention Breaking:** Agents often use generic patterns instead of your project's specific helpers or base classes.

**This toolkit solves this by providing:**
- **Machine-Readable Metadata:** Hidden JSON blocks at the top of every report for instant ingestion by LLMs.
- **Symbol-to-Path Mapping:** Every class, model, and service is linked to its exact relative path (e.g., `UserController (framework/Admin/Controllers/UserController.php)`).
- **Functional Lineage:** A "Trace Table" that maps `Route -> Controller -> View` in a single line, giving the agent a straight line to any feature.
- **House Rules:** Automated detection of project-specific constraints (e.g., "Must extend BaseController" or "Use FormBuilder widgets").

---

## ✨ Key Features

### 📋 generate:dossier
Generates a comprehensive `PROJECT_DOSSIER.md` architecture map.
- **Functional Lineage:** Instantly see which controller method and view template handle a specific route.
- **Recursive Service Mapping:** Identifies all services and—crucially—who is calling them (Controllers, Twig templates, or other Services).
- **Model Intelligence:** Distinguishes between Active Record (Eloquent) and Entity patterns, including relationship detection.
- **Indented Tree Visualization:** A clear, hierarchical view of your project structure that preserves path context.

### 🛡️ generate:audit
Generates a high-signal `PROJECT_AUDIT.md` health and risk report.
- **Risk Assessment Heatmap:** Identifies "dangerous" files based on cyclomatic complexity and lines of code (LOC).
- **Agent Guidance:** Provides specific "House Rules" based on audit findings (e.g., "This project has Twig syntax errors; lint before every commit").
- **Widget Usage Analysis:** Verifies if registered UI widgets are actually being utilized or if raw HTML is leaking into views.
- **Security & Coverage:** Integrated `composer audit` and dependency vulnerability scanning.

---

## 🚀 Installation & Setup

### Requirements
- PHP 8.2+
- Composer

### Quick Start
1. Clone the repository into your project's tool directory:
   ```bash
   git clone https://github.com/your-repo/project-dossier.git dossier
   cd dossier && composer install
   ```

2. Generate your first Dossier:
   ```bash
   php src/bin/app.php generate:dossier /path/to/your/project -o PROJECT_DOSSIER.md
   ```

3. Run a Project Audit:
   ```bash
   php src/bin/app.php generate:audit /path/to/your/project -o PROJECT_AUDIT.md
   ```

---

## ⚙️ Configuration (`dossier.config.php`)

For projects with non-standard structures, create a `dossier.config.php` in your project root. The toolkit will automatically detect this and adjust its scan paths:

```php
<?php
return [
    'name' => 'MyCustomApp',
    'paths' => [
        'controllers' => ['src/Http/Controllers', 'app/Legacy/Controllers'],
        'services' => ['src/Domain/Services', 'common/Services'],
        'models' => ['src/Domain/Models'],
        'views' => ['resources/views', 'templates'],
        'routes' => ['routes/web.php', 'routes/api.php', 'app/routes/map.php'],
    ],
    'ignore' => ['vendor', 'node_modules', '.git', 'storage', 'cache'],
];
```

---

## 📊 Output Deep Dive

### The Functional Lineage Table
The Dossier generates a "Trace Map" that allows an AI Agent to navigate features without running `grep`:

| Feature / Route | Controller Method | View Template |
| :--- | :--- | :--- |
| `/admin/users` | `UserController::index` | `admin/users/index.twig` |
| `/api/v1/auth` | `AuthApiController::login` | `N/A` |

### The Agent Risk Heatmap
The Audit identifies where the "fragile" parts of your code are, setting boundaries for autonomous agents:

| Risk Level | Factor | Guidance for AI Agents |
| :--- | :--- | :--- |
| 🔴 **High** | Complexity (> 200 LOC) | `BaseController.php` is fragile. Verify changes with a trial run. |
| 🟠 **Medium** | Missing Type Hints | Some services lack return types. Check `common/Services` for implicit behaviors. |
| 🟡 **Low** | Unused Widgets | Registered widgets like `DateWidget` are unused. Safe to refactor. |

---

## 🛠️ Tool Fallback System

The toolkit is designed to be **Zero-Dependency** for the target project. If the target project doesn't have the necessary tools installed, Dossier uses its own internal binaries located in its own `vendor/` directory:
- **PHPStan:** For static analysis and type checking.
- **PHPLOC:** For lines of code and complexity metrics.
- **PHP-Parser:** For deep architectural and class-relationship analysis.
- **Twig-Lint:** For template syntax verification.

---

## 🗺️ Roadmap
- [ ] **Laravel/Symfony Auto-Presets:** Zero-config support for major frameworks.
- [ ] **Database Schema Ingestion:** Add table structures and field types to the Dossier models map.
- [ ] **Agent Instruction Export:** Generate a `.cursorrules` or `.clinerules` file automatically.
- [ ] **Graph Visualization:** Export Mermaid.js code for architectural and relationship diagrams.

## 🤝 Contributing
Contributions are welcome! If you find a pattern that isn't being detected or want to add support for a new framework, please open a PR.

---
**Maintained by the Gemini CLI Community.**
