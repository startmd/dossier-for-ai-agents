# Project Dossier: The Agent-First AI Toolkit

Welcome to **Project Dossier**, the definitive toolkit for making your PHP codebase instantly understandable to AI Coding Assistants like Gemini, Claude, and GitHub Copilot.

## The Problem: The "Token Tax" of Discovery

When an AI Agent is dropped into a fresh, large-scale PHP project, it faces a massive hurdle: **Context Discovery**. 

To write a simple feature or fix a bug, the Agent must:
1. Figure out where the routes are defined.
2. Guess which controller handles a specified route.
3. Guess which Twig or Blade template that controller renders.
4. Try to uncover the project's architectural pattern (MVC? Active Record? Entity mapping?).

The Agent usually solves this by running expensive `find` and `grep` commands in the terminal, reading dozens of completely irrelevant files. This wastes your token limits, eats up the Agent's context window, and ultimately, causes the Agent to hallucinate file paths or lose the thread of the actual problem.

## The Solution: The "Mission Map"

**Project Dossier** solves this by pre-computing the architecture of your application and compiling it into two high-signal, machine-readable artifacts: `PROJECT_DOSSIER.md` and `PROJECT_AUDIT.md`. 

By feeding these files to your AI at the start of a session, you skip the "Discovery Phase". The Agent knows exactly where every file is, exactly how routes connect to views, and exactly what internal constraints it must follow.

---

## Tool 1: `generate:dossier`

The core tool for mapping the functional architecture of your app.

```bash
php src/bin/app.php generate:dossier /path/to/project
```

### What it produces in `PROJECT_DOSSIER.md`:

1. **AI Metadata Header:** A hidden, machine-readable JSON block `<!-- AI_METADATA_START ...` that an LLM can instantly ingest to parse the total scale and framework type of your app.
2. **House Rules (Conventions):** AI guidance formulated directly from the shape of your code (e.g. stating the base classes used).
3. **The Trace Table:** The most vital piece of data for an AI. It manually links Route -> Controller Method -> Rendered View Template side-by-side, so the AI never has to guess how a feature pieces together.
4. **Symbol Maps:** Exact file paths for every Controller, Service, and Model in the project.

---

## Tool 2: `generate:audit`

A specialized command for analyzing code health specifically tailored for *instructing an AI*.

```bash
php src/bin/app.php generate:audit /path/to/project
```

### What it produces in `PROJECT_AUDIT.md`:

1. **Agent Guidance Heatmap:** Instead of standard linting errors, this generates actionable boundaries for the agent. (Example: *"The BaseController is highly complex (>200 LOC). Do not modify without explicit human permission."*)
2. **Widget/Library Usage:** Outlines what libraries are installed and commonly used, forcing the AI to use your existing toolset instead of hallucinating new dependencies.

---

## Advanced Usage: Custom Architectures

Project Dossier is completely zero-dependency for the target project (it ships with its own static analysis tools!).

If your PHP project uses a non-standard folder structure (legacy apps, custom frameworks), simply place a `dossier.config.php` file in the root of the project you are analyzing. The AI Toolkit will prioritize these paths over default Symfony/Laravel conventions.

**Example `dossier.config.php`:**

```php
<?php
return [
    'name' => 'MyApp',
    'paths' => [
        'controllers' => ['src/Web/Controllers', 'app/Api/Endpoints'],
        'services'    => ['src/Domain/UseCases'],
        'models'      => ['src/Infrastructure/Entities'],
        'views'       => ['public/templates', 'resources/views'],
        'routes'      => ['config/routes.php'],
    ],
    'ignore' => ['vendor', 'node_modules', 'storage']
];
```

## How to use with your AI

1. Run the Dossier generators on your project directory.
2. Open your AI Assistant (e.g., Cursor, Gemini CLI, Claude Desktop).
3. Attach `PROJECT_DOSSIER.md` and `PROJECT_AUDIT.md` directly into the chat prompt.
4. Write your prompt: *"Review the attached Dossier. Please add a new endpoint for User Registration following the existing conventions mapped out in the Dossier."*
5. Watch the AI execute precisely without ever running a blind `ls` or `find` command.
