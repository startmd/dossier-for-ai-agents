<?php

declare(strict_types=1);

namespace ProjectDossier;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use PhpParser\ParserFactory;

class DossierCommand extends Command
{
    protected function configure(): void
    {
        $this->setName("generate:dossier")
            ->setDescription("Generates a project dossier for AI/Humans")
            ->addArgument(
                "path",
                InputArgument::OPTIONAL,
                "Relative or absolute path to the project directory",
            )
            ->addOption(
                "output",
                "o",
                InputOption::VALUE_OPTIONAL,
                "Output file name",
                "PROJECT_DOSSIER.md",
            )
            ->addOption(
                "config",
                "c",
                InputOption::VALUE_OPTIONAL,
                "Config file name",
                "dossier.config.php",
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);

        // Get project path
        $path = $input->getArgument("path");

        if (!$path) {
            $path = $io->ask(
                "📁 What is the path to your project directory?",
                null,
                function ($value) use ($io) {
                    if (!$value) {
                        throw new \RuntimeException("Project path is required");
                    }
                    $realPath = realpath($value);
                    if (!$realPath) {
                        throw new \RuntimeException(
                            "Path '{$value}' does not exist",
                        );
                    }
                    if (!file_exists($realPath . "/composer.json")) {
                        $io->warning(
                            "No composer.json found. This may not be a PHP project.",
                        );
                        $confirm = $io->confirm("Continue anyway?", true);
                        if (!$confirm) {
                            throw new \RuntimeException("Aborted by user");
                        }
                    }
                    return $value;
                },
            );
        }

        $root = realpath($path);
        if (!$root) {
            $io->error("Invalid path: {$path}");
            return Command::FAILURE;
        }

        // Load config
        $configFile = $root . "/" . $input->getOption("config");
        $config = file_exists($configFile)
            ? include $configFile
            : $this->getDefaultConfig($root);

        $io->title("Generating Dossier");
        $io->text("Project: <info>{$config["name"]}</info>");
        $io->text("Path: <comment>{$root}</comment>");
        $io->newLine();

        $arch = $this->analyzeArchitecture($root, $config, $output);

        $markdown = [];
        $markdown[] = "# Project Dossier: {$config["name"]}";
        $markdown[] = "";
        
        // 0. AI Agent Metadata (Machine Readable)
        $metadata = [
            "project" => $config["name"],
            "generated" => date("Y-m-d H:i:s"),
            "framework" => "Slim 4 / Custom",
            "paths" => $config["paths"],
            "stats" => [
                "controllers" => count($arch["controllers"]),
                "services" => count($arch["services"]),
                "models" => count($arch["models"]),
                "routes" => count($arch["routes"])
            ]
        ];
        $markdown[] = "<!-- AI_METADATA_START";
        $markdown[] = json_encode($metadata, JSON_PRETTY_PRINT);
        $markdown[] = "AI_METADATA_END -->";
        $markdown[] = "";

        $markdown[] = "> **Generated:** " . date("Y-m-d H:i:s");
        $markdown[] = "> **Path:** {$root}";
        $markdown[] = "> **PHP Version:** " . phpversion();
        $markdown[] = "";

        // 1. Conventions & House Rules
        $markdown[] = "## 1. Conventions & Patterns (Agent Guidance)";
        $markdown[] = "";
        $markdown[] = "Based on project analysis, the following rules apply:";
        $markdown[] = "- **Controller Base:** All Admin controllers must extend `BaseController`.";
        $markdown[] = "- **Model Type:** Use Eloquent models for database interaction.";
        $markdown[] = "- **Service Pattern:** Business logic belongs in `common/Services/`.";
        $markdown[] = "- **View Engine:** Use Twig (`.twig`) for all templates.";
        $markdown[] = "- **Route Registration:** Add new CRUD modules to `app/routes/route_map.php`.";
        $markdown[] = "";

        // 2. Functional Lineage (The "Trace" Map)
        $markdown[] = "## 2. Functional Lineage (Route -> Controller -> View)";
        $markdown[] = "";
        $markdown[] = "| Feature / Route | Controller Method | View Template |";
        $markdown[] = "| :--- | :--- | :--- |";
        foreach ($arch["routes"] as $route) {
            $matchedController = "-";
            $matchedView = "-";
            foreach ($arch["controllers"] as $ctrl) {
                if (str_contains($ctrl["name"], $route["controller"])) {
                    $matchedController = "`" . $ctrl["name"] . "::" . $route["method"] . "`";
                    $matchedView = !empty($ctrl["views"]) ? "`" . implode("`, `", $ctrl["views"]) . "`" : "-";
                    break;
                }
            }
            $markdown[] = "| `{$route["uri"]}` | {$matchedController} | {$matchedView} |";
        }
        $markdown[] = "";

        // 3. Directory Structure
        $output->writeln("  → Scanning directory structure...");
        $markdown[] = "## 3. Directory Structure";
        $markdown[] = "";
        $markdown[] = "```";
        $markdown[] = $this->scanDirectory($root, $config["ignore"] ?? []);
        $markdown[] = "```";
        $markdown[] = "";

        // 4. Libraries
        $output->writeln("  → Analyzing libraries...");
        $markdown[] = "## 4. Libraries & Dependencies";
        $markdown[] = "";
        $markdown[] = "### PHP Dependencies";
        $markdown[] = "";
        $markdown[] = $this->parseComposer($root . "/composer.json");
        $markdown[] = "";

        // 5. Architecture
        $markdown[] = "## 5. Architecture Analysis (MVC+S)";
        $markdown[] = "";
        $markdown[] = "### Pattern Detection";
        $markdown[] = "";
        $markdown[] = "| Aspect | Status | Details |";
        $markdown[] = "|--------|--------|---------|";
        $markdown[] =
            "| **Pattern** | " . ($arch["pattern"] ?? "Unknown") . " | MVC+S |";
        $markdown[] =
            "| **Model Type** | " .
            $arch["modelType"] .
            " | " .
            $arch["modelTypeDetails"] .
            " |";
        $markdown[] =
            "| **Controller Style** | " .
            ($arch["thinControllers"] ? "✅ Thin" : "⚠️ Bloated") .
            " | " .
            $arch["controllerNotes"] .
            " |";
        $markdown[] =
            "| **Service Layer** | " .
            ($arch["hasServices"] ? "✅ Present" : "❌ Missing") .
            " | {$arch["serviceCount"]} services |";
        $markdown[] = "";

        // 6. Controllers
        $output->writeln("  → Mapping controllers...");
        $markdown[] = "## 6. Controllers Map";
        $markdown[] = "";
        if (!empty($arch["controllers"])) {
            $markdown[] = "| Controller | Exact Path | Methods | Status |";
            $markdown[] = "|------------|------------|---------|--------|";
            foreach ($arch["controllers"] as $controller) {
                $methods = implode(", ", array_slice($controller["methods"], 0, 5)) . (count($controller["methods"]) > 5 ? "..." : "");
                $status = $controller["complexity"] === "High" ? "⚠️ Bloated" : "✅";
                $markdown[] = "| {$controller["name"]} | `{$controller["path"]}` | {$methods} | {$status} |";
            }
            $markdown[] = "";
        }

        // 7. Services
        $output->writeln("  → Mapping services...");
        $markdown[] = "## 7. Services Map";
        $markdown[] = "";
        if (!empty($arch["services"])) {
            $markdown[] = "| Service | Exact Path | Methods | Called By |";
            $markdown[] = "|---------|------------|---------|-----------|";
            foreach ($arch["services"] as $service) {
                $methods = implode(", ", array_slice($service["methods"], 0, 3)) . (count($service["methods"]) > 3 ? "..." : "");
                $calledBy = implode(", ", array_unique($service["calledBy"] ?? ["-"]));
                $markdown[] = "| {$service["name"]} | `{$service["path"]}` | {$methods} | {$calledBy} |";
            }
            $markdown[] = "";
        }

        // 8. Models
        $output->writeln("  → Mapping models...");
        $markdown[] = "## 8. Models Map";
        $markdown[] = "";
        if (!empty($arch["models"])) {
            $markdown[] = "| Model | Exact Path | Type | Relationships |";
            $markdown[] = "|-------|------------|------|---------------|";
            foreach ($arch["models"] as $model) {
                $type = $model["type"];
                $relationships = implode(
                    ", ",
                    $model["relationships"] ?? ["-"],
                );
                $markdown[] = "| {$model["name"]} | `{$model["path"]}` | {$type} | {$relationships} |";
            }
            $markdown[] = "";
        }

        // 8. Routes Summary
        $output->writeln("  → Analyzing routes...");
        $markdown[] = "## 8. Routes Summary";
        $markdown[] = "";
        $markdown[] = "| Method | URI | Controller | Status |";
        $markdown[] = "|--------|-----|------------|--------|";
        foreach ($arch["routes"] ?? [] as $route) {
            $status = $route["registered"] ? "✅" : "⚠️ Unregistered";
            $markdown[] = "| {$route["method"]} | {$route["uri"]} | {$route["controller"]} | {$status} |";
        }
        $markdown[] = "";

        // 9. Quick Reference
        $markdown[] = "## 9. Quick Reference for AI Agents";
        $markdown[] = "";
        $markdown[] = "### Entry Points";
        $markdown[] =
            "- **Main Controller:** " . ($arch["mainController"] ?? "Unknown");
        $markdown[] =
            "- **Auth Controller:** " . ($arch["authController"] ?? "Unknown");
        $markdown[] =
            "- **API Controller:** " . ($arch["apiController"] ?? "Unknown");
        $markdown[] = "";
        $markdown[] = "### Critical Services";
        $markdown[] = implode(
            "\n",
            array_map(
                fn($s) => "- `{$s["name"]}`",
                array_slice($arch["services"] ?? [], 0, 5),
            ),
        );
        $markdown[] = "";
        $markdown[] = "### High-Risk Areas";
        $markdown[] = implode(
            "\n",
            array_map(
                fn($c) => "- `{$c["name"]}` ({$c["complexity"]} complexity)",
                array_slice($arch["complexControllers"] ?? [], 0, 3),
            ),
        );
        $markdown[] = "";

        $outputFile = $root . "/" . $input->getOption("output");
        file_put_contents($outputFile, implode("\n", $markdown));

        $io->success("Dossier generated: {$outputFile}");
        return Command::SUCCESS;
    }

    private function getDefaultConfig(string $root): array
    {
        return [
            "name" => basename($root),
            "ignore" => [
                "vendor",
                "node_modules",
                ".git",
                "storage",
                "cache",
                "logs",
            ],
            "paths" => [
                "controllers" => [
                    "app/Http/Controllers",
                    "app/Controllers",
                    "src/Controller",
                ],
                "services" => [
                    "app/Services",
                    "app/Domain/Services",
                    "src/Service",
                ],
                "models" => ["app/Models", "app/Entities", "src/Entity"],
                "views" => ["resources/views", "templates", "views"],
                "routes" => [
                    "routes/web.php",
                    "routes/api.php",
                    "config/routes.php",
                ],
            ],
        ];
    }

    private function scanDirectory(string $root, array $ignore): string
    {
        $finder = new Finder();
        $finder->in($root)
            ->depth("< 4")
            ->notPath($ignore);

        $files = [];
        foreach ($finder as $file) {
            $files[] = str_replace($root . DIRECTORY_SEPARATOR, "", $file->getRelativePathname());
        }
        sort($files);

        $tree = "";
        $lastPath = [];
        foreach ($files as $file) {
            $parts = explode(DIRECTORY_SEPARATOR, $file);
            foreach ($parts as $i => $part) {
                if (!isset($lastPath[$i]) || $lastPath[$i] !== $part) {
                    $indent = str_repeat("  ", $i);
                    $fullRelativePath = implode(DIRECTORY_SEPARATOR, array_slice($parts, 0, $i + 1));
                    $isDir = is_dir($root . DIRECTORY_SEPARATOR . $fullRelativePath);
                    $tree .= $indent . $part . ($isDir ? "/" : "") . "\n";
                }
            }
            $lastPath = $parts;
        }

        return $tree;
    }

    private function parseComposer(string $path): string
    {
        if (!file_exists($path)) {
            return "> `composer.json` not found";
        }

        $json = json_decode(file_get_contents($path), true);
        if (!$json) {
            return "> Invalid `composer.json`";
        }

        $lines = [];
        $lines[] = "| Package | Version | Type |";
        $lines[] = "|---------|---------|------|";

        foreach ($json["require"] ?? [] as $pkg => $ver) {
            $type = (str_contains($pkg, "laravel") || str_contains($pkg, "symfony") || str_contains($pkg, "slim") || str_contains($pkg, "illuminate"))
                ? "Framework/Core"
                : "Library";
            $lines[] = "| `{$pkg}` | {$ver} | {$type} |";
        }

        return implode("\n", $lines);
    }

    private function parsePackage(string $path): string
    {
        if (!file_exists($path)) {
            return "> `package.json` not found";
        }

        $json = json_decode(file_get_contents($path), true);
        if (!$json) {
            return "> Invalid `package.json`";
        }

        $frontend = [
            "tailwindcss",
            "alpinejs",
            "vue",
            "react",
            "axios",
            "lodash",
            "bootstrap",
        ];
        $found = [];

        foreach ($json["dependencies"] ?? [] as $pkg => $ver) {
            if (in_array($pkg, $frontend) || str_contains($pkg, "tailwind")) {
                $found[] = "`{$pkg}` ({$ver})";
            }
        }

        foreach ($json["devDependencies"] ?? [] as $pkg => $ver) {
            if (in_array($pkg, $frontend) || str_contains($pkg, "tailwind")) {
                $found[] = "`{$pkg}` ({$ver}) [dev]";
            }
        }

        return empty($found)
            ? "> No common frontend libraries detected"
            : implode(", ", $found);
    }

    private function analyzeArchitecture(string $root, array $config, $output): array
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $result = [
            "pattern" => "MVC+S",
            "modelType" => "Unknown",
            "modelTypeDetails" => "Need manual verification",
            "thinControllers" => true,
            "controllerNotes" => "Not analyzed",
            "hasServices" => false,
            "serviceCount" => 0,
            "controllers" => [],
            "services" => [],
            "models" => [],
            "routes" => [],
            "complexControllers" => [],
        ];

        // Analyze Controllers
        foreach ($config["paths"]["controllers"] ?? [] as $controllerPath) {
            $fullPath = $root . "/" . ltrim($controllerPath, "/");
            if (is_dir($fullPath)) {
                $controllers = $this->parseControllers(
                    $fullPath,
                    $parser,
                    $root,
                );
                $result["controllers"] = array_merge(
                    $result["controllers"],
                    $controllers,
                );
            }
        }

        // Check controller thickness
        $bloatedCount = 0;
        foreach ($result["controllers"] as &$controller) {
            if ($controller["linesOfCode"] > 200) {
                $bloatedCount++;
                $controller["complexity"] = "High";
                $result["complexControllers"][] = $controller;
            }
        }

        if ($bloatedCount > 0) {
            $result["thinControllers"] = false;
            $result[
                "controllerNotes"
            ] = "{$bloatedCount} controllers exceed 200 lines";
        } else {
            $result["controllerNotes"] = "All controllers are within size limits";
        }

        // Analyze Services
        foreach ($config["paths"]["services"] ?? [] as $servicePath) {
            $fullPath = $root . "/" . ltrim($servicePath, "/");
            if (is_dir($fullPath)) {
                $services = $this->parseServices($fullPath, $parser, $root);
                $result["services"] = array_merge(
                    $result["services"],
                    $services,
                );
            }
        }

        $result["hasServices"] = !empty($result["services"]);
        $result["serviceCount"] = count($result["services"]);

        // Analyze Models
        foreach ($config["paths"]["models"] ?? [] as $modelPath) {
            $fullPath = $root . "/" . ltrim($modelPath, "/");
            if (is_dir($fullPath)) {
                $models = $this->parseModels($fullPath, $parser, $root);
                $result["models"] = array_merge($result["models"], $models);

                // Detect model type
                if (!empty($models)) {
                    $firstModel = reset($models);
                    if ($firstModel["type"] === "Active Record") {
                        $result["modelType"] = "Active Record";
                        $result["modelTypeDetails"] =
                            "Models extend base Model class (Eloquent style)";
                    } elseif ($firstModel["type"] === "Entity") {
                        $result["modelType"] = "Entity + Table";
                        $result["modelTypeDetails"] =
                            "Plain PHP objects with separate data mappers (Doctrine style)";
                    }
                }
            }
        }

        // Parse Routes
        foreach ($config["paths"]["routes"] ?? [] as $routeFile) {
            $fullPath = $root . "/" . ltrim($routeFile, "/");
            if (file_exists($fullPath)) {
                $routes = $this->parseRoutes($fullPath);
                $result["routes"] = array_merge($result["routes"], $routes);
            }
        }

        // Cross-reference services: Find where they are used
        $output->writeln("  → Cross-referencing services...");
        $this->crossReferenceServices($root, $result["services"], $result["controllers"]);

        // Map routes to controllers
        foreach ($result["routes"] as $route) {
            foreach ($result["controllers"] as &$controller) {
                // Controller names in routes are often shorthand (e.g., 'User')
                if (str_contains($controller["name"], $route["controller"]) || 
                    str_contains($route["controller"], $controller["name"])) {
                    $controller["routes"][] = $route["uri"];
                }
            }
        }

        // Find main controllers
        foreach ($result["controllers"] as $controller) {
            $name = strtolower($controller["name"]);
            if (str_contains($name, "home") || str_contains($name, "main") || str_contains($name, "dashboard")) {
                $result["mainController"] = $controller["name"];
            }
            if (str_contains($name, "auth") || str_contains($name, "login") || str_contains($name, "user")) {
                $result["authController"] = $controller["name"];
            }
            if (str_contains($name, "api")) {
                $result["apiController"] = $controller["name"];
            }
        }

        return $result;
    }

    private function crossReferenceServices(string $root, array &$services, array $controllers): void
    {
        $finder = new Finder();
        $files = $finder->in($root)
            ->path(["app", "framework", "common", "public", "routes", "bootstrap", "config"])
            ->name(["*.php", "*.twig"])
            ->files();

        foreach ($services as &$service) {
            $name = $service["name"];
            foreach ($files as $file) {
                $content = file_get_contents($file->getPathname());
                $relativePath = str_replace($root . DIRECTORY_SEPARATOR, "", $file->getPathname());
                
                // Look for ClassName usages (injection, instantiation, or static calls)
                if (str_contains($content, $name)) {
                    // Avoid self-reference
                    if (!str_contains($relativePath, $name)) {
                        $caller = basename($relativePath);
                        if (!in_array($caller, $service["calledBy"] ?? [])) {
                            $service["calledBy"][] = $caller;
                        }
                    }
                }
            }
            if (isset($service["calledBy"])) {
                $service["calledBy"] = array_slice(array_unique($service["calledBy"]), 0, 8);
            }
        }
    }


    private function parseControllers(
        string $path,
        $parser,
        string $root,
    ): array {
        $controllers = [];
        $finder = new Finder();

        try {
            $files = $finder->in($path)->name("*.php")->files();
        } catch (\Exception $e) {
            return [];
        }

        foreach ($files as $file) {
            $content = file_get_contents($file->getPathname());
            try {
                $ast = $parser->parse($content);
            } catch (\Exception $e) {
                continue;
            }

            $classInfo = $this->extractClassInfo($ast, $content);
            if ($classInfo && (str_contains($classInfo["name"], "Controller") || str_contains($file->getFilename(), "Controller"))) {
                $controllers[] = [
                    "name" => $classInfo["name"],
                    "path" => str_replace($root . DIRECTORY_SEPARATOR, "", $file->getPathname()),
                    "methods" => $classInfo["methods"],
                    "linesOfCode" => substr_count($content, "\n"),
                    "routes" => [],
                    "views" => $this->extractViews($content),
                    "hasMissingRoutes" => false,
                    "complexity" => "Normal",
                ];
            }
        }

        return $controllers;
    }


    private function parseServices(string $path, $parser, string $root): array
    {
        $services = [];
        $finder = new Finder();

        try {
            $files = $finder->in($path)->name("*.php")->files();
        } catch (\Exception $e) {
            return [];
        }

        foreach ($files as $file) {
            $content = file_get_contents($file->getPathname());
            try {
                $ast = $parser->parse($content);
            } catch (\Exception $e) {
                continue;
            }

            $classInfo = $this->extractClassInfo($ast, $content);
            if (
                $classInfo &&
                (str_contains($classInfo["name"], "Service") ||
                    $classInfo["name"] === "Manager" ||
                    str_contains($classInfo["name"], "Manager") ||
                    str_contains($path, "Service"))
            ) {
                $services[] = [
                    "name" => $classInfo["name"],
                    "path" => str_replace($root . DIRECTORY_SEPARATOR, "", $file->getPathname()),
                    "methods" => $classInfo["methods"],
                    "calledBy" => [],
                ];
            }
        }

        return $services;
    }

    private function parseModels(string $path, $parser, string $root): array
    {
        $models = [];
        $finder = new Finder();

        try {
            $files = $finder->in($path)->name("*.php")->files();
        } catch (\Exception $e) {
            return [];
        }

        foreach ($files as $file) {
            $content = file_get_contents($file->getPathname());
            try {
                $ast = $parser->parse($content);
            } catch (\Exception $e) {
                continue;
            }

            $classInfo = $this->extractClassInfo($ast, $content);
            if ($classInfo) {
                $type = "Entity";
                if (
                    str_contains($content, "extends Model") ||
                    str_contains($content, "extends Eloquent") ||
                    str_contains($content, "use Model") ||
                    str_contains($content, "Illuminate\\Database\\Eloquent")
                ) {
                    $type = "Active Record";
                }

                $models[] = [
                    "name" => $classInfo["name"],
                    "path" => str_replace($root . DIRECTORY_SEPARATOR, "", $file->getPathname()),
                    "type" => $type,
                    "relationships" => $this->extractRelationships($content),
                ];
            }
        }

        return $models;
    }

    private function extractClassInfo(?array $ast, string $content): ?array
    {
        if (!$ast) {
            return null;
        }

        $className = null;
        $methods = [];

        $walker = function ($nodes) use (&$walker, &$className, &$methods) {
            foreach ($nodes as $node) {
                if ($node instanceof \PhpParser\Node\Stmt\Class_ || $node instanceof \PhpParser\Node\Stmt\Interface_) {
                    if ($node->name) {
                        $className = $node->name->toString();
                        foreach ($node->getMethods() as $method) {
                            if ($method->isPublic()) {
                                $methods[] = $method->name->toString();
                            }
                        }
                        return true;
                    }
                }
                if ($node instanceof \PhpParser\Node\Stmt\Namespace_) {
                    if ($walker($node->stmts)) {
                        return true;
                    }
                }
            }
            return false;
        };

        $walker($ast);

        if (!$className) {
            return null;
        }

        return [
            "name" => $className,
            "methods" => $methods,
        ];
    }

    private function extractViews(string $content): array
    {
        $views = [];
        // Pattern: view('template') or view("template")
        if (preg_match_all('/view\([\'"]([^\'"]+)[\'"]/', $content, $matches)) {
            $views = array_merge($views, $matches[1]);
        }
        // Pattern: ->render($response, 'template.twig')
        if (preg_match_all('/render\([^,]+,\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
            $views = array_merge($views, $matches[1]);
        }
        return array_unique($views);
    }

    private function extractRelationships(string $content): array
    {
        $relationships = [];
        // Catch Eloquent relationships like: public function orders() { return $this->hasMany(...) }
        // Very permissive regex for different formatting styles
        preg_match_all(
            "/(?:function\s+)?(\w+)\s*\([^)]*\)\s*(?::\s*[\w\\\]+\s*)?\{[^}]*?return\s+\$this->(hasMany|belongsTo|hasOne|belongsToMany|morphTo|morphMany|morphToMany|hasManyThrough|hasOneThrough)/s",
            $content,
            $matches,
        );
        return array_unique($matches[1] ?? []);
    }


    private function parseRoutes(string $path): array
    {
        $content = file_get_contents($path);
        $routes = [];

        // 1. Array-based Map (route_map.php or route_map_api.php)
        if (str_contains($content, 'return [')) {
            preg_match_all('/[\'"]([^\'"]+)[\'"]\s*=>\s*(?:[\'"]([^\'"]+)[\'"]|([\w\\\]+)::class)/', $content, $matches);
            foreach ($matches[1] as $i => $uri) {
                $controller = !empty($matches[2][$i]) ? $matches[2][$i] : (!empty($matches[3][$i]) ? $matches[3][$i] : "Unknown");
                $parts = explode('\\', $controller);
                $className = end($parts);
                
                $routes[] = [
                    "method" => "CRUD",
                    "uri" => "/" . $uri,
                    "controller" => $className,
                    "registered" => true,
                ];
            }
        }

        // 2. Slim 4 & Laravel style registration
        // Handle: $app->get('/uri', [Controller::class, 'method']) 
        // Or: $group->post('/uri', '\Namespace\Controller:method')
        // Or: Route::get('/uri', [Controller::class, 'method'])
        preg_match_all(
            '/(?:\$app|\$group|Route)->(get|post|put|delete|patch)\([\'"]([^\'"]+)[\'"]\s*,\s*(?:[\[\(][\'"]?([\w\\\]+)|[\'"]?([\w\\\]+)[:@])/',
            $content,
            $matches,
        );
        foreach ($matches[0] as $i => $match) {
            $controller = !empty($matches[3][$i]) ? $matches[3][$i] : (!empty($matches[4][$i]) ? $matches[4][$i] : "Unknown");
            $parts = explode('\\', $controller);
            $className = str_replace('::class', '', end($parts));
            
            $routes[] = [
                "method" => strtoupper($matches[1][$i]),
                "uri" => $matches[2][$i],
                "controller" => $className,
                "registered" => true,
            ];
        }

        return $routes;
    }


}
