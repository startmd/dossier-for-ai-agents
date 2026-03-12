#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once __DIR__ . "/../../vendor/autoload.php";

use Symfony\Component\Console\Application;
use ProjectDossier\DossierCommand;
use ProjectDossier\AuditCommand;

$application = new Application("Project Dossier", "1.0.0");
$application->add(new DossierCommand());
$application->add(new AuditCommand());
$application->run();

