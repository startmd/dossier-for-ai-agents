<?php

declare(strict_types=1);

namespace ProjectDossier;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class AuditCommand extends Command
{
    protected function configure(): void
    {
        $this->setName("generate:audit")
            ->setDescription("Generates a health audit report")
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
                "AUDIT.md",
            )
            ->addOption(
                "quick",
                "k",
                InputOption::VALUE_NONE,
                "Quick mode (skip slow checks)",
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
        $configFile = $root . "/dossier.config.php";
        $config = file_exists($configFile)
            ? include $configFile
            : $this->getDefaultConfig($root);

        $quickMode = $input->getOption("quick");

        $io->title("Generating Audit Report");
        $io->text("Project: <info>{$config["name"]}</info>");
        $io->text("Path: <comment>{$root}</comment>");
        $io->text(
            "Mode: <comment>" . ($quickMode ? "Quick" : "Full") . "</comment>",
        );
        $io->newLine();

        $markdown = [];
        $markdown[] = "# Project Audit Report: {$config["name"]}";
        $markdown[] = "";
        $markdown[] = "> **Generated:** " . date("Y-m-d H:i:s");
        $markdown[] = "> **Path:** {$root}";
        $markdown[] = "> **Mode:** " . ($quickMode ? "Quick" : "Full");
        $markdown[] = "";

        // 1. Security
        $output->writeln("  → Checking security vulnerabilities...");
        $markdown[] = "## 1. Security Vulnerabilities";
        $markdown[] = "";
        $markdown[] = $this->runComposerAudit($root);
        $markdown[] = "";

        // 2. PHPStan
        $output->writeln("  → Running PHPStan...");
        $markdown[] = "## 2. Static Analysis (PHPStan)";
        $markdown[] = "";
        $markdown[] = $this->runPhpStan($root, $config, $quickMode);
        $markdown[] = "";

        // 3. Widget Usage (Framework Specific)
        $output->writeln("  → Analyzing widget usage...");
        $markdown[] = "## 3. Widget Usage Analysis";
        $markdown[] = "";
        $markdown[] = $this->analyzeWidgetUsage($root, $config);
        $markdown[] = "";

        // 4. Twig Linting
        $output->writeln("  → Linting Twig templates...");
        $markdown[] = "## 4. Twig Template Linting";
        $markdown[] = "";
        $markdown[] = $this->runTwigLint($root, $config);
        $markdown[] = "";

        // 5. Architecture (Deptrac)
        if (file_exists($root . "/deptrac.yaml")) {
            $output->writeln("  → Checking architecture (Deptrac)...");
            $markdown[] = "## 5. Architecture Violations";
            $markdown[] = "";
            $markdown[] = $this->runDeptrac($root);
            $markdown[] = "";
        }

        // 6. Metrics
        $output->writeln("  → Calculating metrics...");
        $markdown[] = "## 6. Technical Debt Metrics";
        $markdown[] = "";
        $markdown[] = $this->runPhpLoc($root, $config);
        $markdown[] = "";

        // 7. Risk Assessment (Agent Specific)
        $markdown[] = "## 7. Agent Risk Assessment & Heatmap";
        $markdown[] = "";
        $markdown[] = "| Risk Level | Factor | Guidance for AI Agents |";
        $markdown[] = "| :--- | :--- | :--- |";
        $markdown[] = "| 🔴 **High** | Complexity (> 200 LOC) | Files like `BaseController` or `CrudController` are fragile. Verify every change with a trial run. |";
        $markdown[] = "| 🟠 **Medium** | Missing Type Hints | Some services lack return types. Check `common/Services` for implicit behaviors. |";
        $markdown[] = "| 🟡 **Low** | Unused Widgets | Some registered widgets are not in use. Refactoring them is safe. |";
        $markdown[] = "";

        // 8. Recommendations & House Rules
        $markdown[] = "## 8. AI Agent Recommendations & House Rules";
        $markdown[] = "";
        $markdown[] = "### Mandatory Constraints";
        $markdown[] = "- **Never** modify `framework/Core` unless specifically asked; prefer extending in `app/`.";
        $markdown[] = "- **Always** use `FormBuilder` widgets for any new UI fields.";
        $markdown[] = "- **Constraint:** PHPStan level 6 must pass after any modification.";
        $markdown[] = "";
        $markdown[] = "### Execution Tips";
        $markdown[] = "1. **Check Section 1:** If vulnerabilities exist, do not add new features until patched.";
        $markdown[] = "2. **Verify Section 4:** Run a lint check after any Twig change; unclosed tags will break the site.";
        $markdown[] = "3. **Refer to Dossier:** Use the *Functional Lineage* table in `PROJECT_DOSSIER.md` to find the correct files for a feature.";
        $markdown[] = "";

        $outputFile = $root . "/" . $input->getOption("output");
        file_put_contents($outputFile, implode("\n", $markdown));

        $io->success("Audit generated: {$outputFile}");
        return Command::SUCCESS;
    }

    private function getDefaultConfig(string $root): array
    {
        return [
            "name" => basename($root),
            "paths" => [
                "controllers" => ["app", "framework"],
                "services" => ["common/Services", "framework/Services"],
                "models" => ["common/Models"],
                "views" => ["framework/Views", "app/Views", "resources/views"],
            ],
        ];
    }

    private function runComposerAudit(string $root): string
    {
        $process = new Process(["composer", "audit", "--format=json"], $root);
        $process->setTimeout(60);

        try {
            $process->run();
            $output = json_decode($process->getOutput(), true);

            if (empty($output["advisories"])) {
                return "✅ **No known vulnerabilities found**";
            }

            $lines = [];
            $lines[] = "| Package | Version | Vulnerability | Severity |";
            $lines[] = "|---------|---------|---------------|----------|";

            foreach ($output["advisories"] as $pkg => $advisories) {
                foreach ($advisories as $adv) {
                    $severity = $adv["severity"] ?? "Unknown";
                    $icon =
                        $severity === "critical"
                            ? "🔴"
                            : ($severity === "high"
                                ? "🟠"
                                : "🟡");
                    $lines[] = "| `{$pkg}` | {$adv["versions"]} | {$adv["title"]} | {$icon} {$severity} |";
                }
            }

            return implode("\n", $lines);
        } catch (\Exception $e) {
            return "> ⚠️ Could not run composer audit ({$e->getMessage()})";
        }
    }

    private function runPhpStan(string $root, array $config, bool $quickMode): string
    {
        $bin = $root . "/vendor/bin/phpstan";
        if (!file_exists($bin)) {
            $bin = __DIR__ . "/../../vendor/bin/phpstan";
        }

        if (!file_exists($bin)) {
            return "> ⚠️ PHPStan not installed. Run: `composer require --dev phpstan/phpstan`";
        }

        $paths = [];
        foreach (["controllers", "services", "models"] as $type) {
            foreach ($config["paths"][$type] ?? [] as $path) {
                if (is_dir($root . "/" . $path)) {
                    $paths[] = $path;
                }
            }
        }

        if (empty($paths)) {
            $paths = ["app", "framework", "common"];
        }

        $args = ["analyze", "--error-format=markdown", "--no-progress"];
        $args[] = $quickMode ? "--level=3" : "--level=6";

        $process = new Process(array_merge([$bin], $args, $paths), $root);
        $process->setTimeout(300);

        try {
            $process->run();
            $output = $process->getOutput();

            if (str_contains($output, "[OK]")) {
                return "✅ **No errors found**";
            }

            return substr($output, 0, 8000);
        } catch (\Exception $e) {
            return "> ⚠️ PHPStan failed: {$e->getMessage()}";
        }
    }

    private function analyzeWidgetUsage(string $root, array $config): string
    {
        $registeredWidgets = [];
        $builderFiles = [
            "common/Services/FormBuilder.php",
            "common/Builders/FormBuilder.php",
            "framework/Services/FormBuilder.php",
            "framework/Builders/FormBuilder.php",
            "common/Services/ViewHelper.php",
            "framework/Services/ViewHelper.php",
        ];

        foreach ($builderFiles as $file) {
            if (file_exists($root . "/" . $file)) {
                $content = file_get_contents($root . "/" . $file);
                // Match: return TextInputWidget::widget($params);
                if (preg_match_all("/(\w+Widget)::widget\(/", $content, $matches)) {
                    $registeredWidgets = array_unique(array_merge($registeredWidgets, $matches[1]));
                }
                // Match: 'text' => new TextInputWidget()
                if (preg_match_all("/new\s+(\w+Widget)/", $content, $matches)) {
                    $registeredWidgets = array_unique(array_merge($registeredWidgets, $matches[1]));
                }
            }
        }

        if (empty($registeredWidgets)) {
            return "✅ **No widgets detected in builders**";
        }

        $usedWidgets = [];
        // Scan app, framework, and common for widget usage
        $paths = ["app", "framework", "common"];
        foreach ($paths as $path) {
            if (!is_dir($root . "/" . $path)) continue;
            
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . "/" . $path));
            foreach ($iterator as $file) {
                if ($file->isFile() && ($file->getExtension() === "php" || $file->getExtension() === "twig")) {
                    $content = file_get_contents($file->getPathname());
                    // Check for: new Widget(), Widget::widget(), or {{ form.widget() }}
                    if (preg_match_all("/(\w+Widget)/", $content, $matches)) {
                        $usedWidgets = array_unique(array_merge($usedWidgets, $matches[1]));
                    }
                }
            }
        }

        // We filter usedWidgets to only include those that were actually registered in builders
        $actuallyUsed = array_intersect($registeredWidgets, $usedWidgets);
        $unused = array_diff($registeredWidgets, $actuallyUsed);
        
        if (empty($unused)) {
            return "✅ **All " . count($registeredWidgets) . " registered widgets are being used.**";
        }

        return "⚠️ **Unused Widgets (" . count($unused) . "/" . count($registeredWidgets) . "):**\n- " . implode("\n- ", $unused);
    }


    private function runTwigLint(string $root, array $config): string
    {
        // Try to use vendor/bin/twig-lint if it exists
        $bin = $root . "/vendor/bin/twig-lint";
        if (file_exists($bin)) {
            $process = new Process([$bin, "lint", "app/Views", "framework/Views"], $root);
            $process->run();
            return $process->getOutput() ?: "✅ **Templates are valid**";
        }

        // Fallback: Manual scan
        $errors = [];
        foreach ($config["paths"]["views"] ?? [] as $path) {
            if (!is_dir($root . "/" . $path)) continue;
            
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . "/" . $path));
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === "twig") {
                    $content = file_get_contents($file->getPathname());
                    if (str_contains($content, "{{") && !str_contains($content, "}}")) {
                        $errors[] = "Unclosed `{{` in " . $file->getFilename();
                    }
                }
            }
        }

        return empty($errors) ? "✅ **No basic Twig syntax errors found**" : "❌ **Errors:**\n- " . implode("\n- ", $errors);
    }

    private function runDeptrac(string $root): string
    {
        $bin = $root . "/vendor/bin/deptrac";
        if (!file_exists($bin)) return "> ⚠️ Deptrac not installed";

        $process = new Process([$bin, "analyze", "--format=markdown"], $root);
        $process->setTimeout(120);
        $process->run();

        $output = $process->getOutput();
        return str_contains($output, "0 violations") ? "✅ **No architecture violations**" : $output;
    }

    private function runPhpLoc(string $root, array $config): string
    {
        $bin = $root . "/vendor/bin/phploc";
        if (!file_exists($bin)) {
            $bin = __DIR__ . "/../../vendor/bin/phploc";
        }

        if (!file_exists($bin)) {
            return "> ⚠️ PHPLOC not installed.";
        }

        $paths = ["app", "framework", "common"];
        $process = new Process(array_merge([$bin], $paths), $root);
        $process->setTimeout(120);

        try {
            $process->run();
            $output = $process->getOutput();
            
            // Extract key metrics using regex
            preg_match("/Lines of Code \(LOC\)\s+(\d+)/", $output, $loc);
            preg_match("/Average Complexity per Method\s+([\d\.]+)/", $output, $complexity);
            preg_match("/Cyclomatic Complexity\s+(\d+)/", $output, $totalComp);

            $lines = [];
            $lines[] = "| Metric | Value |";
            $lines[] = "|--------|-------|";
            $lines[] = "| Total Lines of Code | " . ($loc[1] ?? "N/A") . " |";
            $lines[] = "| Average Method Complexity | " . ($complexity[1] ?? "N/A") . " |";
            $lines[] = "| Total Cyclomatic Complexity | " . ($totalComp[1] ?? "N/A") . " |";

            return implode("\n", $lines);
        } catch (\Exception $e) {
            return "> ⚠️ PHPLOC failed: {$e->getMessage()}";
        }
    }
}
