<?php

declare(strict_types=1);

require('bootstrap.php');

use Przeslijmi\Sinstaller\Installer;
use Przeslijmi\Sinstaller\CriticalException;

try {

    // Create installer.
    $ins = new Installer();
    $ins->setComposer('resources/forTesting/testComposer.json');

    // Additional settings - enabled by default.
    $ins->disableCriticalStop();
    $ins->enableCriticalStop();

    // Echoing - enabled by default.
    $ins->getLogger()->disableEcho();
    $ins->getLogger()->enableEcho();

    $ins->makeDir('tests/probe/src/');
    $ins->fileIfne('Vendor\\App', 'Exception.php', 'tests/probe/Exception.php');
    $ins->dir('Vendor\\App', '.', 'tests/probe/src/aaa/');
    $ins->setFileContentsIfe('tests/probe/src/aaa/Exception.php', 'aa');
    $ins->setFileContentsIfne('tests/probe/src/aaa/Exception.php', 'aa');
    $ins->emptyDirRecursively('tests/probe/src/aaa/');

    $ins->getLogger()->logLn('[NL][NL]Installation finished![NL]');
    $ins->getLogger()->logLn('[NL]Start console with commands:[NL]----------------------------[NL]cd [currDir][NL]php -S 127.0.0.1:8001[NL]----------------------------');

} catch (CriticalException $exc) {
    die('ciritcal stop occured');
}
