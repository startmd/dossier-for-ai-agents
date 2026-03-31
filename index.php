<?php
$output = '';
$error = '';
$path = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $path = trim($_POST['project_path'] ?? '');
    $action = $_POST['action'] ?? '';

    if (empty($path)) {
        $error = "Please provide an absolute path to the project directory.";
    } elseif (!is_dir($path)) {
        $error = "The directory '$path' does not exist or is not accessible.";
    } else {
        $command = '';
        $binPath = realpath(__DIR__ . '/src/bin/app.php');
        
        if (!$binPath) {
            $error = "Could not locate the CLI application at src/bin/app.php.";
        } else {
            $escapedPath = escapeshellarg($path);
            
            if ($action === 'dossier') {
                $command = "php " . escapeshellarg($binPath) . " generate:dossier " . $escapedPath . " 2>&1";
            } elseif ($action === 'audit') {
                $command = "php " . escapeshellarg($binPath) . " generate:audit " . $escapedPath . " 2>&1";
            } else {
                $error = "Invalid action specified.";
            }

            if ($command) {
                // Execute and capture output
                exec($command, $outputLines, $returnVar);
                $output = implode("\n", $outputLines);
                
                if ($returnVar !== 0) {
                    $error = "Command failed with status code: $returnVar. Please check permissions.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Dossier - Agent Mission Control</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.0/css/bulma.min.css">
    <style>
        :root {
            /* Black, White, Gold Theme */
            --page-bg: #0d0d0d;
            --panel-bg: #141414;
            --text-main: #f5f5f5;
            --gold-accent: #d4af37;
            --gold-hover: #b8962c;
            --input-bg: #1a1a1a;
            --border-color: #333;
        }

        body {
            background-color: var(--page-bg);
            color: var(--text-main);
            min-height: 100vh;
            font-family: 'Inter', BlinkMacSystemFont, -apple-system, "Segoe UI", "Roboto", "Oxygen", "Ubuntu", "Cantarell", "Fira Sans", "Droid Sans", "Helvetica Neue", "Helvetica", "Arial", sans-serif;
        }

        h1, h2, h3, h4, h5, h6, .title, .subtitle, label {
            color: var(--text-main) !important;
        }

        .hero.is-dark {
            background-color: transparent;
            border-bottom: 1px solid var(--border-color);
        }

        .hero-title {
            background: linear-gradient(90deg, #fff, var(--gold-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
        }

        .card {
            background-color: var(--panel-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        }

        .input.is-dark {
            background-color: var(--input-bg);
            border-color: var(--border-color);
            color: var(--text-main);
        }
        
        .input.is-dark:focus {
            border-color: var(--gold-accent);
            box-shadow: 0 0 0 0.125em rgba(212, 175, 55, 0.25);
        }
        
        .input.is-dark::placeholder {
            color: #666;
        }

        .button.is-gold {
            background-color: var(--gold-accent);
            border-color: transparent;
            color: #000;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .button.is-gold:hover {
            background-color: var(--gold-hover);
            color: #000;
            transform: translateY(-1px);
        }

        .button.is-outline-gold {
            background-color: transparent;
            border-color: var(--gold-accent);
            color: var(--gold-accent);
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .button.is-outline-gold:hover {
            background-color: rgba(212, 175, 55, 0.1);
            color: var(--gold-accent);
        }

        /* Terminal Window Styling */
        .terminal-container {
            background-color: #050505;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            overflow: hidden;
            margin-top: 2rem;
        }

        .terminal-header {
            background-color: #1a1a1a;
            padding: 8px 15px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #333;
        }

        .terminal-dots {
            display: flex;
            gap: 6px;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .dot.red { background-color: #ff5f56; }
        .dot.yellow { background-color: #ffbd2e; }
        .dot.green { background-color: #27c93f; }

        .terminal-title {
            color: #888;
            font-family: monospace;
            font-size: 0.85rem;
            margin-left: 15px;
        }

        .terminal-output {
            padding: 20px;
            background: transparent;
            color: #a9b7c6;
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.5;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
        }

        .terminal-output .success { color: var(--gold-accent); }
    </style>
</head>
<body>

    <!-- Hero Section -->
    <section class="hero is-dark">
        <div class="hero-body">
            <div class="container">
                <h1 class="title is-2 hero-title">Project Dossier</h1>
                <p class="subtitle is-5 mt-2">Agent-First Intelligence Toolkit for PHP</p>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="section">
        <div class="container" style="max-width: 800px;">
            
            <div class="content" style="color: #ccc; margin-bottom: 2rem;">
                <h3 style="color: var(--text-main);">The "Token Tax" of Discovery</h3>
                <p>When an AI Coding Assistant drops into a fresh PHP project, it faces a massive hurdle: Context Discovery. To write a simple feature, the Agent searches directories, guesses routing structures, and hallucinates paths. This "Discovery Phase" burns through token limits quickly.</p>
                <p><strong>Project Dossier</strong> solves this by pre-computing your architecture into a static "Mission Map." Use the controls below to generate high-signal files for your AI:</p>
                
                <div class="columns mt-4">
                    <div class="column">
                        <div class="box" style="background-color: var(--panel-bg); border: 1px solid var(--border-color);">
                            <h4 style="color: var(--gold-accent);">🗺️ Dossier</h4>
                            <p class="is-size-7">Generates <code>PROJECT_DOSSIER.md</code>, tracing Route &rarr; Controller &rarr; View logic.</p>
                        </div>
                    </div>
                    <div class="column">
                        <div class="box" style="background-color: var(--panel-bg); border: 1px solid var(--border-color);">
                            <h4 style="color: var(--gold-accent);">🛡️ Audit</h4>
                            <p class="is-size-7">Generates <code>PROJECT_AUDIT.md</code>, creating automated 'House Rules' and risk heatmaps.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-5">
                <form method="POST" action="">
                    <div class="field">
                        <label class="label">Target Project Directory</label>
                        <div class="control">
                            <input class="input is-dark" type="text" name="project_path" placeholder="e.g., /var/www/html/my-legacy-app" value="<?php echo htmlspecialchars($path); ?>" required>
                        </div>
                        <p class="help" style="color: #888;">Enter the absolute path to the PHP project you want to map.</p>
                    </div>

                    <div class="field is-grouped mt-5">
                        <div class="control">
                            <button class="button is-gold" type="submit" name="action" value="dossier">
                                🗺️ Generate Dossier
                            </button>
                        </div>
                        <div class="control">
                            <button class="button is-outline-gold" type="submit" name="action" value="audit">
                                🛡️ Generate Audit
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Error Notification -->
            <?php if (!empty($error)): ?>
                <div class="notification is-danger is-light mt-5">
                    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Output Terminal -->
            <?php if (!empty($output)): ?>
                <div class="terminal-container">
                    <div class="terminal-header">
                        <div class="terminal-dots">
                            <div class="dot red"></div>
                            <div class="dot yellow"></div>
                            <div class="dot green"></div>
                        </div>
                        <div class="terminal-title">bash - project-dossier-cli</div>
                    </div>
                    <div class="terminal-output"><?php echo htmlspecialchars($output); ?></div>
                </div>
            <?php endif; ?>

        </div>
    </section>

</body>
</html>
